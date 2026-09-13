<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Edit role"
            subtitle="Update role name and permissions."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Roles', 'url' => route('roles.index')],
                ['label' => $role->name],
            ]"
        >
            <x-slot:actions>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to roles
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                @if ($role->is_system)
                    <div class="crm-banner crm-banner--info crm-keep-alert mb-3" role="status">
                        <div class="crm-banner__icon" aria-hidden="true"><i class="fas fa-info-circle"></i></div>
                        <div class="crm-banner__body">
                            This is a system role. Its slug cannot be changed, but you can update its permissions.
                        </div>
                    </div>
                @endif

                <x-form-section title="Role details">
                    <div class="form-group">
                        <x-form-label for="name" :required="true">Name</x-form-label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $role->name) }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <x-form-label for="slug" :required="true">Slug</x-form-label>
                        <input id="slug" name="slug" type="text"
                               class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $role->slug) }}"
                               @if ($role->is_system) readonly @endif
                               required>
                        @error('slug')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="description">Description</x-form-label>
                        <textarea id="description" name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $role->description) }}</textarea>
                        @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Permissions" description="Users with this role will receive the selected permissions.">
                    <x-permission-checklist
                        :module-permissions="$modulePermissions"
                        :selected="array_map('intval', old('permissions', $role->permissions->pluck('id')->all()))"
                    />
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Update role
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
