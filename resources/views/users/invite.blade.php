<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Invite teammate"
            subtitle="Send an email invitation so they can set their own password."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Users', 'url' => route('users.index')],
                ['label' => 'Invite'],
            ]"
        />
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('users.invite.store') }}">
            @csrf
            <div class="card-body">
                <x-form-section title="Invitee" description="We'll email them a secure link to join your workspace.">
                    <div class="form-group">
                        <x-form-label for="name" :required="true">Full name</x-form-label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required autocomplete="name">
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="email" :required="true">Email</x-form-label>
                        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Access" description="Choose roles for the invited teammate.">
                    <div class="form-group mb-0">
                        <x-form-label :required="true">Roles</x-form-label>
                        @php
                            $defaultRoleIds = old('roles', [$roles->firstWhere('slug', 'sales')?->id]);
                        @endphp
                        @foreach ($roles as $role)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input @error('roles') is-invalid @enderror"
                                       id="role-{{ $role->id }}"
                                       name="roles[]"
                                       value="{{ $role->id }}"
                                       @checked(in_array($role->id, (array) $defaultRoleIds, false))>
                                <label class="custom-control-label" for="role-{{ $role->id }}">{{ $role->name }}</label>
                            </div>
                        @endforeach
                        @error('roles')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Send invitation</button>
                <a href="{{ route('users.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
