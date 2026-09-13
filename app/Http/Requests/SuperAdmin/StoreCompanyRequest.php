<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Company;
use App\Services\SuperAdmin\CompanySoftDeleteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        // Free emails/slugs still held by previously soft-deleted tenants so
        // create validation does not falsely report "email already taken".
        app(CompanySoftDeleteService::class)->releaseIdentifiersForTrashedCompanies();

        $this->merge([
            'subscription_status' => $this->input('subscription_status', Company::SUBSCRIPTION_TRIAL),
            'status' => $this->input('status', Company::STATUS_ACTIVE),
            'admin_email' => filled($this->input('admin_email'))
                ? strtolower(trim((string) $this->input('admin_email')))
                : $this->input('admin_email'),
            'email' => filled($this->input('email'))
                ? strtolower(trim((string) $this->input('email')))
                : $this->input('email'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('companies', 'slug')->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
            'status' => ['required', Rule::in(array_keys(Company::STATUSES))],
            'subscription_status' => ['required', Rule::in(array_keys(Company::SUBSCRIPTION_STATUSES))],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'trial_ends_at' => ['nullable', 'date'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => [
                'nullable',
                'required_with:admin_password',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'admin_password' => ['nullable', 'required_with:admin_email', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_password.required_with' => 'An admin password is required when creating a company admin account.',
            'admin_email.unique' => 'This admin email is already used by an active account.',
        ];
    }
}
