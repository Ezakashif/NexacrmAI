<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\PlanFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($plan)],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'yearly_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly', 'both'])],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_free' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'features' => ['nullable', 'array', 'max:100'],
            'features.*.feature_key' => ['required_with:features', 'string', 'max:100', 'alpha_dash', 'distinct'],
            'features.*.feature_name' => ['required_with:features', 'string', 'max:160'],
            'features.*.description' => ['nullable', 'string', 'max:2000'],
            'features.*.feature_type' => ['required_with:features', Rule::in(PlanFeature::TYPES)],
            'features.*.feature_value' => ['nullable', 'string', 'max:255'],
            'features.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'features.*.is_highlighted' => ['nullable', 'boolean'],
            'limits' => ['nullable', 'array', 'max:100'],
            'limits.*.limit_key' => ['required_with:limits', 'string', 'max:100', 'alpha_dash', 'distinct'],
            'limits.*.limit_name' => ['required_with:limits', 'string', 'max:160'],
            'limits.*.limit_value' => ['nullable', 'string', 'max:255'],
            'limits.*.unit' => ['nullable', 'string', 'max:40'],
            'limits.*.description' => ['nullable', 'string', 'max:2000'],
            'limits.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
