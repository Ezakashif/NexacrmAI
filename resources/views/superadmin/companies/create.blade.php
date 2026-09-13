@extends('superadmin.layout')

@section('title', 'New company')
@section('heading', 'Create company')
@section('subheading', 'Provision a tenant and optional first admin')

@section('content')
<div class="sa-card" style="max-width: 760px;">
    <form method="POST" action="{{ route('superadmin.companies.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="sa-form-section">
            <h2 class="sa-form-section__title">Company details</h2>
            <p class="sa-form-section__hint">Basic identity and contact information for the tenant.</p>

            <div class="form-group">
                <label class="sa-required">Company name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Slug <span class="sa-muted">(optional)</span></label>
                    <input type="text" name="slug" value="{{ old('slug') }}" class="form-control" placeholder="acme-crm">
                </div>
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                </div>
                <div class="form-group col-md-6">
                    <label>Logo</label>
                    <input type="file" name="logo" class="form-control-file text-white">
                </div>
            </div>
        </div>

        <div class="sa-form-section">
            <h2 class="sa-form-section__title">Plan &amp; status</h2>
            <p class="sa-form-section__hint">Subscription state applied when the tenant is created.</p>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label class="sa-required">Status</label>
                    <select name="status" class="custom-select" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label class="sa-required">Subscription</label>
                    <select name="subscription_status" class="custom-select" required>
                        @foreach ($subscriptionStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('subscription_status', 'trial') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Plan</label>
                    <select name="plan_id" class="custom-select">
                        <option value="">Default</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group mb-0">
                <label>Trial ends at <span class="sa-muted">(optional)</span></label>
                <input type="datetime-local" name="trial_ends_at" value="{{ old('trial_ends_at') }}" class="form-control">
            </div>
        </div>

        <div class="sa-form-section">
            <h2 class="sa-form-section__title">First company admin <span class="sa-muted font-weight-normal">(optional)</span></h2>
            <p class="sa-form-section__hint">Optionally provision the first administrator for this workspace.</p>

            <div class="form-group">
                <label>Admin name</label>
                <input type="text" name="admin_name" value="{{ old('admin_name') }}" class="form-control">
            </div>

            <div class="form-group">
                <label>Admin email</label>
                <input type="email" name="admin_email" value="{{ old('admin_email') }}" class="form-control">
            </div>

            <div class="form-group mb-0">
                <label for="admin_password">Admin password</label>
                <x-password-input
                    name="admin_password"
                    id="admin_password"
                    autocomplete="new-password"
                    class="@error('admin_password') is-invalid @enderror"
                />
                <small class="form-text text-muted">
                    At least 10 characters, with upper and lower case, a number, and a symbol.
                    Leave blank to auto-generate a strong password.
                </small>
                @error('admin_password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex">
            <button type="submit" class="btn btn-info mr-2">Create company</button>
            <a href="{{ route('superadmin.companies.index') }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
