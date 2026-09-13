<?php

namespace App\Services\Csv;

use App\Models\Customer;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\CrmValidation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class CustomerCsvImporter
{
    /** @var list<string> */
    public const HEADERS = [
        'name',
        'email',
        'phone',
        'company_name',
        'address',
        'notes',
    ];

    public function __construct(
        protected CsvReader $reader,
    ) {}

    public function import(User $actor, UploadedFile $file): CsvImportResult
    {
        $parsed = $this->reader->read($file);
        $result = new CsvImportResult;
        $seenEmails = [];

        foreach ($parsed['rows'] as $row) {
            $rowNumber = $row['row'];
            $data = CsvValueNormalizer::applyHeaderAliases($row['data'], [
                'company' => 'company_name',
            ]);

            $email = CsvValueNormalizer::email($data['email'] ?? null);

            $payload = [
                'name' => $data['name'] ?? '',
                'email' => $email,
                'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
                'company_name' => ($data['company_name'] ?? '') !== '' ? $data['company_name'] : null,
                'address' => ($data['address'] ?? '') !== '' ? $data['address'] : null,
                'notes' => ($data['notes'] ?? '') !== '' ? $data['notes'] : null,
            ];

            $validator = Validator::make($payload, CrmValidation::customerStoreRules(forImport: true));

            if ($validator->fails()) {
                $result->addError($rowNumber, $validator->errors()->first());

                continue;
            }

            $validated = $validator->validated();
            $normalizedEmail = CsvValueNormalizer::email($validated['email'] ?? null);

            if ($normalizedEmail !== null) {
                if (isset($seenEmails[$normalizedEmail])) {
                    $result->addDuplicate(
                        $rowNumber,
                        "Duplicate email in file: {$normalizedEmail} (same as row {$seenEmails[$normalizedEmail]})"
                    );

                    continue;
                }

                if (Customer::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists()) {
                    $result->addDuplicate(
                        $rowNumber,
                        "Customer with email already exists: {$normalizedEmail}"
                    );

                    continue;
                }

                $seenEmails[$normalizedEmail] = $rowNumber;
            }

            $customer = null;

            try {
                app(\App\Services\PlanLimitService::class)->assertCanAddCustomer($actor->company);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $result->addError($rowNumber, collect($exception->errors())->flatten()->first() ?: 'Customer plan limit reached.');

                continue;
            }

            $customer = Customer::create([
                'created_by' => $actor->id,
                'name' => $validated['name'],
                'email' => $normalizedEmail,
                'phone' => $validated['phone'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
            ]);

            ActivityLogger::log('customer.created', $customer, [
                'name' => $customer->name,
                'via' => 'csv_import',
            ]);

            $result->imported++;
        }

        return $result;
    }
}
