<?php

namespace App\Services\Csv;

use App\Models\Lead;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\CrmValidation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class LeadCsvImporter
{
    /** @var list<string> */
    public const HEADERS = [
        'name',
        'email',
        'phone',
        'company',
        'source',
        'estimated_value',
        'notes',
        'follow_up_date',
        'assigned_to',
    ];

    public function __construct(
        protected CsvReader $reader,
    ) {}

    public function import(User $actor, UploadedFile $file): CsvImportResult
    {
        $parsed = $this->reader->read($file);
        $result = new CsvImportResult;
        $seenEmails = [];
        $sortOrder = (int) Lead::query()->where('status', 'new')->max('sort_order');

        foreach ($parsed['rows'] as $row) {
            $rowNumber = $row['row'];
            $data = $row['data'];
            $email = CsvValueNormalizer::email($data['email'] ?? null);
            $assignedEmail = CsvValueNormalizer::email($data['assigned_to'] ?? null);

            $payload = [
                'name' => $data['name'] ?? '',
                'email' => $email,
                'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
                'company' => ($data['company'] ?? '') !== '' ? $data['company'] : null,
                'source' => ($data['source'] ?? '') !== '' ? strtolower((string) $data['source']) : null,
                'estimated_value' => ($data['estimated_value'] ?? '') !== '' ? $data['estimated_value'] : null,
                'notes' => ($data['notes'] ?? '') !== '' ? $data['notes'] : null,
                'follow_up_date' => ($data['follow_up_date'] ?? '') !== '' ? $data['follow_up_date'] : null,
            ];

            if ($actor->canAssignLeads()) {
                $payload['assigned_to'] = $assignedEmail;
            }

            $validator = Validator::make($payload, CrmValidation::leadStoreRules($actor, forImport: true));

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

                if (Lead::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists()) {
                    $result->addDuplicate(
                        $rowNumber,
                        "Lead with email already exists: {$normalizedEmail}"
                    );

                    continue;
                }

                $seenEmails[$normalizedEmail] = $rowNumber;
            }

            $assignedTo = $actor->id;

            if ($actor->canAssignLeads()) {
                $assigneeEmail = CsvValueNormalizer::email($validated['assigned_to'] ?? null);
                $assignedTo = $assigneeEmail
                    ? User::query()->whereRaw('LOWER(email) = ?', [$assigneeEmail])->value('id')
                    : null;
            }

            $sortOrder++;

            try {
                app(\App\Services\PlanLimitService::class)->assertCanAddLead($actor->company);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $result->addError($rowNumber, collect($exception->errors())->flatten()->first() ?: 'Lead plan limit reached.');

                continue;
            }

            $lead = Lead::create([
                'created_by' => $actor->id,
                'assigned_to' => $assignedTo,
                'name' => $validated['name'],
                'email' => $normalizedEmail,
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'] ?? null,
                'source' => $validated['source'] ?? null,
                'status' => 'new',
                'sort_order' => $sortOrder,
                'estimated_value' => $validated['estimated_value'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
            ]);

            ActivityLogger::log('lead.created', $lead, [
                'name' => $lead->name,
                'via' => 'csv_import',
            ]);

            $result->imported++;
        }

        return $result;
    }
}
