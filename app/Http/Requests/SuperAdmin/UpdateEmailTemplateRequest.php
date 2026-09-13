<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailTemplateRequest extends FormRequest
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
        $locales = array_keys(config('email_templates.locales', ['en' => 'English']));

        return [
            'locale' => [
                'required',
                'string',
                Rule::in($locales),
                Rule::unique('email_templates', 'locale')
                    ->where(fn ($query) => $query->where('category', $this->route('email_template')?->category))
                    ->ignore($this->route('email_template')?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'use_branding' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'use_branding' => $this->boolean('use_branding'),
        ]);
    }
}
