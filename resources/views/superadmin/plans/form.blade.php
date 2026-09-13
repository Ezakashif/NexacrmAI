@php
    $features = old('features', $plan->features?->map(fn ($feature) => $feature->only(['feature_key', 'feature_name', 'description', 'feature_type', 'feature_value', 'sort_order', 'is_highlighted']))->all() ?? []);
    $limits = old('limits', $plan->limits?->map(fn ($limit) => $limit->only(['limit_key', 'limit_name', 'limit_value', 'unit', 'description', 'sort_order']))->all() ?? []);
@endphp
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="sa-form-section">
    <h2 class="sa-form-section__title">General</h2>
    <div class="form-row">
        <div class="form-group col-md-6"><label>Name</label><input class="form-control" name="name" value="{{ old('name', $plan->name) }}" required></div>
        <div class="form-group col-md-6"><label>Slug</label><input class="form-control" name="slug" value="{{ old('slug', $plan->slug) }}" required></div>
    </div>
    <div class="form-group"><label>Short description</label><input class="form-control" name="short_description" value="{{ old('short_description', $plan->short_description) }}"></div>
    <div class="form-group"><label>Full description</label><textarea class="form-control" name="description" rows="4">{{ old('description', $plan->description) }}</textarea></div>
</div>
<div class="sa-form-section">
    <h2 class="sa-form-section__title">Pricing</h2>
    <div class="form-row">
        <div class="form-group col-md-3"><label>Monthly price</label><input class="form-control" type="number" step="0.01" min="0" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price ?? 0) }}" required></div>
        <div class="form-group col-md-3"><label>Yearly price</label><input class="form-control" type="number" step="0.01" min="0" name="yearly_price" value="{{ old('yearly_price', $plan->yearly_price ?? 0) }}" required></div>
        <div class="form-group col-md-2"><label>Currency</label><input class="form-control" name="currency" value="{{ old('currency', $plan->currency ?? 'USD') }}" required></div>
        <div class="form-group col-md-2"><label>Billing</label><select class="custom-select" name="billing_cycle">@foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly', 'both' => 'Both'] as $value => $label)<option value="{{ $value }}" @selected(old('billing_cycle', $plan->billing_cycle ?? 'both') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group col-md-2"><label>Trial days</label><input class="form-control" type="number" min="0" name="trial_days" value="{{ old('trial_days', $plan->trial_days ?? 14) }}" required></div>
        <div class="form-group col-md-2"><label>Sort order</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" required></div>
    </div>
</div>
<div class="sa-form-section"><h2 class="sa-form-section__title">Status</h2>
@foreach (['is_active' => 'Active', 'is_public' => 'Public', 'is_featured' => 'Featured', 'is_free' => 'Free'] as $field => $label)
<div class="custom-control custom-checkbox custom-control-inline"><input class="custom-control-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $plan->$field ?? false))><label class="custom-control-label" for="{{ $field }}">{{ $label }}</label></div>
@endforeach
</div>
<div class="sa-form-section"><h2 class="sa-form-section__title">Internal notes</h2><textarea class="form-control" name="notes" rows="3">{{ old('notes', $plan->notes) }}</textarea></div>
<div class="sa-form-section" id="plan-details">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="sa-form-section__title mb-0">Features</h2><button class="btn btn-sm btn-outline-light" type="button" data-add-feature>Add feature</button></div>
    <p class="sa-muted small">Features are shown publicly in this order when the plan is public.</p>
    <div data-feature-list>
        @foreach ($features as $index => $feature)
            <div class="border rounded p-3 mb-2" data-feature-row>
                <div class="form-row"><div class="form-group col-md-4"><label>Key</label><input class="form-control" name="features[{{ $index }}][feature_key]" value="{{ $feature['feature_key'] ?? '' }}"></div><div class="form-group col-md-4"><label>Name</label><input class="form-control" name="features[{{ $index }}][feature_name]" value="{{ $feature['feature_name'] ?? '' }}"></div><div class="form-group col-md-2"><label>Type</label><select class="custom-select" name="features[{{ $index }}][feature_type]">@foreach (\App\Models\PlanFeature::TYPES as $type)<option value="{{ $type }}" @selected(($feature['feature_type'] ?? 'boolean') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div><div class="form-group col-md-2"><label>Order</label><input class="form-control" type="number" min="0" name="features[{{ $index }}][sort_order]" value="{{ $feature['sort_order'] ?? $index + 1 }}"></div></div>
                <div class="form-row"><div class="form-group col-md-6"><label>Value</label><input class="form-control" name="features[{{ $index }}][feature_value]" value="{{ $feature['feature_value'] ?? '' }}"></div><div class="form-group col-md-4"><label>Description</label><input class="form-control" name="features[{{ $index }}][description]" value="{{ $feature['description'] ?? '' }}"></div><div class="form-group col-md-2 pt-4"><div class="custom-control custom-checkbox"><input class="custom-control-input" id="feature-highlight-{{ $index }}" type="checkbox" name="features[{{ $index }}][is_highlighted]" value="1" @checked($feature['is_highlighted'] ?? false)><label class="custom-control-label" for="feature-highlight-{{ $index }}">Highlight</label></div></div></div>
                <button class="btn btn-sm btn-outline-danger" type="button" data-remove-row>Remove</button>
            </div>
        @endforeach
    </div>
    <template data-feature-template><div class="border rounded p-3 mb-2" data-feature-row><div class="form-row"><div class="form-group col-md-4"><label>Key</label><input class="form-control" name="features[__INDEX__][feature_key]"></div><div class="form-group col-md-4"><label>Name</label><input class="form-control" name="features[__INDEX__][feature_name]"></div><div class="form-group col-md-2"><label>Type</label><select class="custom-select" name="features[__INDEX__][feature_type]">@foreach (\App\Models\PlanFeature::TYPES as $type)<option value="{{ $type }}">{{ ucfirst($type) }}</option>@endforeach</select></div><div class="form-group col-md-2"><label>Order</label><input class="form-control" type="number" min="0" name="features[__INDEX__][sort_order]" value="0"></div></div><div class="form-row"><div class="form-group col-md-6"><label>Value</label><input class="form-control" name="features[__INDEX__][feature_value]"></div><div class="form-group col-md-4"><label>Description</label><input class="form-control" name="features[__INDEX__][description]"></div><div class="form-group col-md-2 pt-4"><div class="custom-control custom-checkbox"><input class="custom-control-input" id="feature-highlight-__INDEX__" type="checkbox" name="features[__INDEX__][is_highlighted]" value="1"><label class="custom-control-label" for="feature-highlight-__INDEX__">Highlight</label></div></div></div><button class="btn btn-sm btn-outline-danger" type="button" data-remove-row>Remove</button></div></template>
</div>
<div class="sa-form-section">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="sa-form-section__title mb-0">Usage limits</h2><button class="btn btn-sm btn-outline-light" type="button" data-add-limit>Add limit</button></div>
    <div data-limit-list>@foreach ($limits as $index => $limit)<div class="border rounded p-3 mb-2" data-limit-row><div class="form-row"><div class="form-group col-md-3"><label>Key</label><input class="form-control" name="limits[{{ $index }}][limit_key]" value="{{ $limit['limit_key'] ?? '' }}"></div><div class="form-group col-md-3"><label>Name</label><input class="form-control" name="limits[{{ $index }}][limit_name]" value="{{ $limit['limit_name'] ?? '' }}"></div><div class="form-group col-md-2"><label>Value</label><input class="form-control" name="limits[{{ $index }}][limit_value]" value="{{ $limit['limit_value'] ?? '' }}" placeholder="Blank = unlimited"></div><div class="form-group col-md-2"><label>Unit</label><input class="form-control" name="limits[{{ $index }}][unit]" value="{{ $limit['unit'] ?? '' }}"></div><div class="form-group col-md-2"><label>Order</label><input class="form-control" type="number" min="0" name="limits[{{ $index }}][sort_order]" value="{{ $limit['sort_order'] ?? $index + 1 }}"></div></div><div class="form-group"><label>Description</label><input class="form-control" name="limits[{{ $index }}][description]" value="{{ $limit['description'] ?? '' }}"></div><button class="btn btn-sm btn-outline-danger" type="button" data-remove-row>Remove</button></div>@endforeach</div>
    <template data-limit-template><div class="border rounded p-3 mb-2" data-limit-row><div class="form-row"><div class="form-group col-md-3"><label>Key</label><input class="form-control" name="limits[__INDEX__][limit_key]"></div><div class="form-group col-md-3"><label>Name</label><input class="form-control" name="limits[__INDEX__][limit_name]"></div><div class="form-group col-md-2"><label>Value</label><input class="form-control" name="limits[__INDEX__][limit_value]" placeholder="Blank = unlimited"></div><div class="form-group col-md-2"><label>Unit</label><input class="form-control" name="limits[__INDEX__][unit]"></div><div class="form-group col-md-2"><label>Order</label><input class="form-control" type="number" min="0" name="limits[__INDEX__][sort_order]" value="0"></div></div><div class="form-group"><label>Description</label><input class="form-control" name="limits[__INDEX__][description]"></div><button class="btn btn-sm btn-outline-danger" type="button" data-remove-row>Remove</button></div></template>
</div>
@once
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',function(){[['feature','[data-feature-list]'],['limit','[data-limit-list]']].forEach(function(pair){var type=pair[0],list=document.querySelector(pair[1]),button=document.querySelector('[data-add-'+type+']'),template=document.querySelector('[data-'+type+'-template]');if(button){button.addEventListener('click',function(){var index=list.children.length;list.insertAdjacentHTML('beforeend',template.innerHTML.replaceAll('__INDEX__',index));});}});document.addEventListener('click',function(e){if(e.target.matches('[data-remove-row]'))e.target.closest('[data-feature-row],[data-limit-row]').remove();});});</script>
@endpush
@endonce
