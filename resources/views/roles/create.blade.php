<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Create role"
            subtitle="Name a role and choose permissions."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Roles', 'url' => route('roles.index')],
                ['label' => 'Create'],
            ]"
        />
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            <div class="card-body">
                <x-form-section title="Role details" description="Identify this role for your team.">
                    <div class="form-group">
                        <x-form-label for="name" :required="true">Name</x-form-label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <x-form-label for="slug" :required="true">Slug</x-form-label>
                        <input id="slug" name="slug" type="text" class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug') }}" placeholder="e.g. support_agent" required>
                        <small class="form-text text-muted">Lowercase letters, numbers, dashes and underscores only.</small>
                        @error('slug')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="description">Description</x-form-label>
                        <textarea id="description" name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Permissions" description="Select which permissions this role grants to assigned users.">
                    <x-permission-checklist
                        :module-permissions="$modulePermissions"
                        :selected="array_map('intval', old('permissions', []))"
                    />
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Save role
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
