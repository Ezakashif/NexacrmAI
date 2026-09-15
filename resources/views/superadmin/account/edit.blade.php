@extends('superadmin.layout')

@section('title', 'Account')
@section('heading', 'Account')
@section('subheading', 'Change the password for your Super Admin login')

@section('content')
<div class="sa-card" style="max-width: 560px;">
    @if (session('status') === 'password-updated')
        <div class="alert alert-success sa-keep-alert">
            Password updated. Other devices were signed out.
        </div>
    @endif

    <form method="POST" action="{{ route('superadmin.password.update') }}">
        @csrf
        @method('PUT')

        <div class="sa-form-section">
            <h2 class="sa-form-section__title">Change password</h2>
            <p class="sa-form-section__hint">Use a long, unique password. Other devices will be signed out after a successful change.</p>

            <div class="form-group">
                <label class="sa-required" for="update_password_current_password">Current password</label>
                <x-password-input
                    name="current_password"
                    id="update_password_current_password"
                    autocomplete="current-password"
                    :required="true"
                    class="@error('current_password', 'updatePassword') is-invalid @enderror"
                />
                @error('current_password', 'updatePassword')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="sa-required" for="update_password_password">New password</label>
                <x-password-input
                    name="password"
                    id="update_password_password"
                    autocomplete="new-password"
                    :required="true"
                    class="@error('password', 'updatePassword') is-invalid @enderror"
                />
                <small class="sa-muted d-block mt-1">
                    At least 10 characters, with upper and lower case, and a symbol.
                </small>
                @error('password', 'updatePassword')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group mb-0">
                <label class="sa-required" for="update_password_password_confirmation">Confirm password</label>
                <x-password-input
                    name="password_confirmation"
                    id="update_password_password_confirmation"
                    autocomplete="new-password"
                    :required="true"
                />
            </div>
        </div>

        <div class="d-flex">
            <button type="submit" class="btn btn-info">Update password</button>
        </div>
    </form>
</div>
@endsection
