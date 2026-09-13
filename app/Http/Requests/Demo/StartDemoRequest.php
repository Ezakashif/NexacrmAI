<?php

namespace App\Http\Requests\Demo;

use App\Support\DemoEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'anonymous' => $this->boolean('anonymous'),
            'contact_consent' => $this->boolean('contact_consent'),
            'email' => filled($this->input('email')) ? strtolower(trim((string) $this->input('email'))) : null,
            'name' => filled($this->input('name')) ? trim((string) $this->input('name')) : null,
            'company' => filled($this->input('company')) ? trim((string) $this->input('company')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $anonymous = $this->boolean('anonymous');

        return [
            'persona' => ['required', 'string', Rule::in(array_keys(DemoEnvironment::personas()))],
            'anonymous' => ['sometimes', 'boolean'],
            'email' => [$anonymous ? 'nullable' : 'required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'contact_consent' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Enter your work email to continue, or choose Continue without email.',
            'email.email' => 'Please enter a valid email address.',
            'persona.required' => 'Choose a demo role to continue.',
            'persona.in' => 'Choose a valid demo role.',
        ];
    }

    public function isAnonymous(): bool
    {
        return $this->boolean('anonymous');
    }
}
