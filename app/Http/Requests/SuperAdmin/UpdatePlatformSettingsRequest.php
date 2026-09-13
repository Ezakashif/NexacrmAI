<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:255'],
            'platform_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
            'remove_logo' => ['nullable', 'boolean'],
            'platform_favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:512'],
            'remove_favicon' => ['nullable', 'boolean'],
            'default_timezone' => ['required', 'timezone'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_linkedin_url' => ['nullable', 'url', 'max:255'],
            'company_facebook_url' => ['nullable', 'url', 'max:255'],
            'company_twitter_url' => ['nullable', 'url', 'max:255'],
            'company_github_url' => ['nullable', 'url', 'max:255'],
            'smtp_host' => ['nullable', 'string', 'max:255', 'required_with:smtp_port,smtp_username,smtp_password', 'regex:/^[A-Za-z0-9.-]+$/'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1024'],
            'smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl', ''])],
            'registration_enabled' => ['nullable', 'boolean'],
            'email_verification_required' => ['nullable', 'boolean'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'trial_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_company_status' => ['required', Rule::in(array_keys(Company::STATUSES))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'smtp_host.regex' => 'SMTP host must be a hostname like smtp.gmail.com (no spaces or protocol).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $host = $this->input('smtp_host');

        if (is_string($host) && $host !== '') {
            $host = strtolower(trim($host));
            $host = preg_replace('#^(smtp|smtps)://#', '', $host) ?? $host;
            $host = rtrim($host, '/');
            // Common typo: smpt.gmail.com → smtp.gmail.com
            $host = preg_replace('/^smpt\./', 'smtp.', $host) ?? $host;

            $this->merge(['smtp_host' => $host]);
        }

        if ($this->input('smtp_encryption') === '') {
            $this->merge(['smtp_encryption' => null]);
        }
    }
}
