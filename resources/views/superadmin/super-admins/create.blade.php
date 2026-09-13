@extends('superadmin.layout')

@section('title', 'Create Super Admin')
@section('heading', 'Create Super Admin')
@section('subheading', 'Add another platform operator')

@section('content')
<div class="sa-card" style="max-width: 560px;">
    <form method="POST" action="{{ route('superadmin.super-admins.store') }}">
        @csrf
        <div class="sa-form-section">
            <h2 class="sa-form-section__title">Operator account</h2>
            <p class="sa-form-section__hint">Credentials for a platform administrator with full console access.</p>

            <div class="form-group">
                <label class="sa-required">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="sa-required">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="sa-required" for="password">Password</label>
                <x-password-input
                    name="password"
                    id="password"
                    autocomplete="new-password"
                    :required="true"
                    class="@error('password') is-invalid @enderror"
                />
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group mb-0">
                <label class="sa-required" for="password_confirmation">Confirm password</label>
                <x-password-input
                    name="password_confirmation"
                    id="password_confirmation"
                    autocomplete="new-password"
                    :required="true"
                />
            </div>
        </div>
        <div class="d-flex">
            <button type="submit" class="btn btn-info mr-2">Create</button>
            <a href="{{ route('superadmin.super-admins.index') }}" class="btn btn-outline-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
