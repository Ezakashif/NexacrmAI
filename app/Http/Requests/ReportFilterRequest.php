<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public const MAX_RANGE_DAYS = 366;

    public function authorize(): bool
    {
        return $this->user()?->canAccessReports() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'employee_id' => [
                'nullable',
                'integer',
                \App\Support\CrmValidation::existsInCompany('users', 'id', $this->user()?->company_id),
            ],
            'source' => ['nullable', Rule::in(Lead::SOURCES)],
            'status' => ['nullable', Rule::in(array_keys(Lead::STATUSES))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $from = $this->input('date_from');
            $to = $this->input('date_to');

            if (! filled($from) || ! filled($to)) {
                return;
            }

            $fromDate = \Carbon\Carbon::parse($from)->startOfDay();
            $toDate = \Carbon\Carbon::parse($to)->startOfDay();

            if ($fromDate->diffInDays($toDate) > self::MAX_RANGE_DAYS) {
                $validator->errors()->add(
                    'date_to',
                    'The report date range may not exceed '.self::MAX_RANGE_DAYS.' days.',
                );
            }
        });
    }

    /**
     * Normalized filters with defaults (last 90 days → today).
     *
     * @return array{
     *     date_from: string,
     *     date_to: string,
     *     employee_id: int|null,
     *     source: string|null,
     *     status: string|null
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'date_from' => $validated['date_from'] ?? now()->subDays(89)->toDateString(),
            'date_to' => $validated['date_to'] ?? now()->toDateString(),
            'employee_id' => isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            'source' => $validated['source'] ?? null,
            'status' => $validated['status'] ?? null,
        ];
    }
}
