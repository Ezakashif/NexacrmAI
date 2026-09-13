<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Roles"
            subtitle="Define permissions for each team role."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Roles'],
            ]"
        >
            <x-slot:actions>
                @can('create', App\Models\Role::class)
                    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add Role
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            {{ $errors->first() }}
        </div>
    @endif

    <x-list-filters :reset-url="route('roles.index')">
        <div class="col-md-4 mb-2">
            <label for="search" class="small text-muted mb-1">Search</label>
            <input id="search" name="search" type="text" class="form-control form-control-sm"
                   placeholder="Name or slug..."
                   value="{{ $filters['search'] ?? '' }}">
        </div>
    </x-list-filters>

    @php
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
        $canCreateRole = auth()->user()->can('create', App\Models\Role::class);
    @endphp

    <div class="card">
        @if ($roles->isEmpty())
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-user-shield"
                    :title="$hasFilters ? 'No roles match your filters' : 'No roles found'"
                    :description="$hasFilters
                        ? 'Try clearing filters or broadening your search.'
                        : 'Create a custom role to grant specific permissions.'"
                    :action-url="$hasFilters ? route('roles.index') : ($canCreateRole ? route('roles.create') : null)"
                    :action-label="$hasFilters ? 'Clear filters' : ($canCreateRole ? 'Add role' : null)"
                />
            </div>
        @else
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Type</th>
                            @canany(['update.roles', 'delete.roles'])
                                <th class="text-right">Actions</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>
                                    <strong>{{ $role->name }}</strong>
                                    @if ($role->description)
                                        <small class="text-muted d-block">{{ $role->description }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td>
                                    @if ($role->is_system)
                                        <span class="badge badge-secondary">System</span>
                                    @else
                                        <span class="badge badge-light">Custom</span>
                                    @endif
                                </td>
                                @canany(['update', 'delete'], $role)
                                    <td class="text-right text-nowrap">
                                        @can('update', $role)
                                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-xs btn-default" aria-label="Edit {{ $role->name }}">
                                                <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                            </a>
                                        @endcan
                                        @can('delete', $role)
                                            @unless ($role->is_system)
                                                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="btn btn-xs btn-danger"
                                                        aria-label="Delete {{ $role->name }}"
                                                        data-crm-confirm="Delete this role? Users with only this role may lose access."
                                                        data-crm-confirm-title="Delete role"
                                                        data-crm-confirm-label="Delete"
                                                    >
                                                        <i class="fas fa-trash" aria-hidden="true"></i> Delete
                                                    </button>
                                                </form>
                                            @endunless
                                        @endcan
                                    </td>
                                @endcanany
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($roles->hasPages())
                <div class="card-footer clearfix">
                    {{ $roles->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
