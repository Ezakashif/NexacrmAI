<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailTemplateRequest extends FormRequest
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
        $categories = array_keys(config('email_templates.categories', []));

        return [
            'category' => ['required', 'string', Rule::in($categories)],
            'locale' => [
                'required',
                'string',
                Rule::in($locales),
                Rule::unique('email_templates', 'locale')->where(fn ($query) => $query->where('category', $this->input('category'))),
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
