@extends('superadmin.layout')

@section('title', 'Edit '.$company->name)
@section('heading', 'Edit company')
@section('subheading', $company->slug)

@section('content')
<div class="sa-card" style="max-width: 760px;">
    <form method="POST" action="{{ route('superadmin.companies.update', $company) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="sa-form-section">
            <h2 class="sa-form-section__title">Company details</h2>
            <p class="sa-form-section__hint">Identity, ownership, and branding for this tenant.</p>

            <div class="form-group">
                <label class="sa-required">Company name</label>
                <input type="text" name="name" value="{{ old('name', $company->name) }}" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label class="sa-required">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $company->slug) }}" class="form-control" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $company->email) }}" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label>Owner</label>
                    <select name="owner_id" class="custom-select">
                        <option value="">None</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((string) old('owner_id', $company->owner_id) === (string) $owner->id)>
                                {{ $owner->name }} ({{ $owner->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group mb-0">
                <label>Logo</label>
                @if ($company->logoUrl())
                    <div class="mb-2">
                        <img src="{{ $company->logoUrl() }}" alt="" style="height:48px;border-radius:0.35rem;">
                    </div>
                    <label class="sa-muted small d-block mb-2">
                        <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Remove current logo
                    </label>
                @endif
                <input type="file" name="logo" class="form-control-file text-white">
            </div>
        </div>

        <div class="sa-form-section">
            <h2 class="sa-form-section__title">Plan &amp; status</h2>
            <p class="sa-form-section__hint">Subscription and access state for this workspace.</p>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="sa-required">Status</label>
                    <select name="status" class="custom-select" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $company->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label class="sa-required">Subscription</label>
                    <select name="subscription_status" class="custom-select" required>
                        @foreach ($subscriptionStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('subscription_status', $company->subscription_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Plan</label>
                    <select name="plan_id" class="custom-select">
                        <option value="">None</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id', $company->plan_id) === (string) $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group mb-0">
                <label>Trial ends at</label>
                <input type="datetime-local" name="trial_ends_at" value="{{ old('trial_ends_at', optional($company->trial_ends_at)->format('Y-m-d\\TH:i')) }}" class="form-control">
            </div>
        </div>

        <div class="d-flex">
            <button type="submit" class="btn btn-info mr-2">Save changes</button>
            <a href="{{ route('superadmin.companies.show', $company) }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
