<?php

namespace App\Http\Requests;

use App\Support\CrmValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Lead::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return CrmValidation::leadStoreRules($this->user());
    }
}
