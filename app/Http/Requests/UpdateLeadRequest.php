<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Support\CrmValidation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Lead $lead */
        $lead = $this->route('lead');

        return $this->user()?->can('update', $lead) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Lead $lead */
        $lead = $this->route('lead');
        $user = $this->user();
        $allowedStatuses = array_keys(Lead::manuallyAssignableStatuses($lead->status));

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|in:'.implode(',', Lead::SOURCES),
            'status' => 'required|in:'.implode(',', $allowedStatuses),
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
        ];

        if ($user?->can('assign', $lead)) {
            $rules['assigned_to'] = ['nullable', CrmValidation::existsInCompany('users', 'id', $user->company_id)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Mark a lead as won by converting it to a customer.',
        ];
    }
}
