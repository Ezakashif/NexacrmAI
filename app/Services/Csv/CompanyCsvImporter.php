<?php

namespace App\Services\Csv;

use App\Models\Company;
use App\Services\CompanyProvisioner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CompanyCsvImporter
{
    /** @var list<string> */
    public const HEADERS = [
        'name',
        'slug',
        'status',
        'admin_name',
        'admin_email',
        'admin_password',
    ];

    public function __construct(
        protected CsvReader $reader,
        protected CompanyProvisioner $provisioner,
    ) {}

    public function import(UploadedFile $file): CsvImportResult
    {
        app(\App\Services\SuperAdmin\CompanySoftDeleteService::class)
            ->releaseIdentifiersForTrashedCompanies();

        $parsed = $this->reader->read($file);
        $result = new CsvImportResult;
        $seenSlugs = [];
        $seenEmails = [];

        foreach ($parsed['rows'] as $row) {
            $rowNumber = $row['row'];
            $data = $row['data'];

            $payload = [
                'name' => trim((string) ($data['name'] ?? '')),
                'slug' => trim((string) ($data['slug'] ?? '')),
                'status' => strtolower(trim((string) ($data['status'] ?? 'active'))) ?: 'active',
                'admin_name' => trim((string) ($data['admin_name'] ?? '')),
                'admin_email' => trim((string) ($data['admin_email'] ?? '')),
                'admin_password' => (string) ($data['admin_password'] ?? ''),
            ];

            $validator = Validator::make($payload, [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:100', 'alpha_dash'],
                'status' => ['required', Rule::in(array_keys(Company::STATUSES))],
                'admin_name' => ['nullable', 'string', 'max:255'],
                'admin_email' => ['nullable', 'required_with:admin_password', 'email', 'max:255'],
                'admin_password' => ['nullable', 'required_with:admin_email', Password::defaults()],
            ]);

            if ($validator->fails()) {
                $result->addError($rowNumber, $validator->errors()->first());

                continue;
            }

            $validated = $validator->validated();
            $slugKey = strtolower((string) ($validated['slug'] ?? $validated['name']));

            if ($slugKey !== '' && isset($seenSlugs[$slugKey])) {
                $result->addDuplicate(
                    $rowNumber,
                    "Duplicate company in file (same as row {$seenSlugs[$slugKey]})."
                );

                continue;
            }

            if (filled($validated['slug'] ?? null) && Company::query()->where('slug', $validated['slug'])->exists()) {
                $result->addDuplicate($rowNumber, "Company slug already exists: {$validated['slug']}");

                continue;
            }

            if (filled($validated['admin_email'] ?? null)) {
                $email = strtolower($validated['admin_email']);

                if (isset($seenEmails[$email])) {
                    $result->addDuplicate(
                        $rowNumber,
                        "Duplicate admin email in file (same as row {$seenEmails[$email]})."
                    );

                    continue;
                }

                if (\App\Models\User::withoutCompanyScope()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    $result->addDuplicate($rowNumber, "Admin email already exists: {$validated['admin_email']}");

                    continue;
                }

                $seenEmails[$email] = $rowNumber;
            }

            try {
                $this->provisioner->provision([
                    'name' => $validated['name'],
                    'slug' => $validated['slug'] ?: null,
                    'status' => $validated['status'],
                    'admin_name' => $validated['admin_name'] ?: null,
                    'admin_email' => $validated['admin_email'] ?: null,
                    'admin_password' => $validated['admin_password'] ?: null,
                ]);
            } catch (\Throwable $e) {
                $result->addError($rowNumber, $e->getMessage());

                continue;
            }

            if ($slugKey !== '') {
                $seenSlugs[$slugKey] = $rowNumber;
            }

            $result->imported++;
        }

        return $result;
    }
}
