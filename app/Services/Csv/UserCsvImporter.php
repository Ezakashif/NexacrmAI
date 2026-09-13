<?php

namespace App\Services\Csv;

use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\CrmValidation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserCsvImporter
{
    /** @var list<string> */
    public const HEADERS = [
        'name',
        'email',
        'password',
        'roles',
        'status',
    ];

    public function __construct(
        protected CsvReader $reader,
    ) {}

    public function import(User $actor, UploadedFile $file): CsvImportResult
    {
        $parsed = $this->reader->read($file);
        $result = new CsvImportResult;
        $seenEmails = [];
        $rolesBySlug = Role::withoutCompanyScope()
            ->when(
                $actor->company_id !== null,
                fn ($query) => $query->where('company_id', $actor->company_id),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->pluck('id', 'slug');

        foreach ($parsed['rows'] as $row) {
            $rowNumber = $row['row'];
            $data = $row['data'];
            $email = CsvValueNormalizer::email($data['email'] ?? null);

            $payload = [
                'name' => $data['name'] ?? '',
                'email' => $email ?? '',
                'password' => $data['password'] ?? '',
                'roles' => $data['roles'] ?? '',
                'status' => ($data['status'] ?? '') !== '' ? strtolower((string) $data['status']) : 'active',
            ];

            $validator = Validator::make($payload, CrmValidation::userStoreRules(forImport: true));

            if ($validator->fails()) {
                $result->addError($rowNumber, $validator->errors()->first());

                continue;
            }

            $validated = $validator->validated();
            $email = CsvValueNormalizer::email($validated['email']);

            if ($email === null) {
                $result->addError($rowNumber, 'A valid email is required.');

                continue;
            }

            if (isset($seenEmails[$email])) {
                $result->addDuplicate(
                    $rowNumber,
                    "Duplicate email in file: {$email} (same as row {$seenEmails[$email]})"
                );

                continue;
            }

            if (User::withoutCompanyScope()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                $result->addDuplicate($rowNumber, "User with email already exists: {$email}");

                continue;
            }

            $roleSlugs = collect(preg_split('/[|,]/', $validated['roles']) ?: [])
                ->map(fn ($slug) => strtolower(trim((string) $slug)))
                ->filter()
                ->unique()
                ->values();

            if ($roleSlugs->isEmpty()) {
                $result->addError($rowNumber, 'At least one role slug is required (e.g. sales).');

                continue;
            }

            $missing = $roleSlugs->reject(fn (string $slug) => $rolesBySlug->has($slug));

            if ($missing->isNotEmpty()) {
                $result->addError($rowNumber, 'Unknown role slug(s): '.$missing->implode(', '));

                continue;
            }

            $roleIds = $roleSlugs->map(fn (string $slug) => (int) $rolesBySlug[$slug])->all();

            try {
                app(\App\Services\PlanLimitService::class)->assertCanAddUser($actor->company);
                app(\App\Services\RoleAssignmentGuard::class)->assertCanAssignRoles($actor, $roleIds);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $result->addError($rowNumber, collect($exception->errors())->flatten()->first() ?: 'Plan or role limit reached.');

                continue;
            }

            $user = new User;
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'role' => 'user',
                'status' => $validated['status'],
                // Admin-provisioned CSV users are trusted and marked verified.
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]);
            $user->company_id = $actor->company_id;
            $user->save();

            $user->syncRoles($roleIds);

            ActivityLogger::log('user.created', $user, [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roleNames(),
                'status' => $user->status,
                'via' => 'csv_import',
            ]);

            $seenEmails[$email] = $rowNumber;
            $result->imported++;
        }

        return $result;
    }
}
