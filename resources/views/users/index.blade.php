<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Users"
            subtitle="Manage team access and account status."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Users'],
            ]"
        >
            <x-slot:actions>
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('exports.users', request()->query()) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-download" aria-hidden="true"></i> Export CSV
                    </a>
                @endcan
                @can('create', App\Models\User::class)
                    <a href="{{ route('imports.create', 'users') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-upload" aria-hidden="true"></i> Import CSV
                    </a>
                    <a href="{{ route('users.invite.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-envelope" aria-hidden="true"></i> Invite
                    </a>
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add User
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if (session('import_errors'))
        <div class="alert alert-warning alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            <strong>Import notes</strong>
            <ul class="mb-0 mt-2">
                @foreach (session('import_errors') as $error)
                    <li>Row {{ $error['row'] }}: {{ $error['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            {{ $errors->first() }}
        </div>
    @endif

    <x-list-filters :reset-url="route('users.index')">
        <div class="col-md-4 mb-2">
            <label for="search" class="small text-muted mb-1">Search</label>
            <input id="search" name="search" type="text" class="form-control form-control-sm"
                   placeholder="Name or email..."
                   value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-3 mb-2">
            <label for="role" class="small text-muted mb-1">Role</label>
            <select id="role" name="role" class="form-control form-control-sm">
                <option value="">All roles</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <label for="status" class="small text-muted mb-1">Status</label>
            <select id="status" name="status" class="form-control form-control-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </x-list-filters>

    @php
        $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
        $canCreateUser = auth()->user()->can('create', App\Models\User::class);
    @endphp

    <div class="card">
        @if ($users->isEmpty())
            <div class="card-body">
                <x-empty-state
                    class="crm-empty--compact"
                    icon="fas fa-users"
                    :title="$hasFilters ? 'No users match your filters' : 'No users yet'"
                    :description="$hasFilters
                        ? 'Try clearing filters or broadening your search.'
                        : 'Invite a teammate to get started.'"
                    :action-url="$hasFilters ? route('users.index') : ($canCreateUser ? route('users.create') : null)"
                    :action-label="$hasFilters ? 'Clear filters' : ($canCreateUser ? 'Add user' : null)"
                />
            </div>
        @else
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px"></th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <x-user-avatar :user="$user" :size="36" />
                                </td>
                                <td>
                                    @can('view', $user)
                                        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                                    @else
                                        {{ $user->name }}
                                    @endcan
                                    @if ($user->id === auth()->id())
                                        <span class="badge badge-light ml-1">You</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge badge-{{ $user->roleBadgeClass() }}">
                                        {{ $user->roleNames() }}
                                    </span>
                                </td>
                                <td>
                                    @can('update', $user)
                                        <form method="POST" action="{{ route('users.status', $user) }}" class="d-inline">
                                            @csrf
                                            <select
                                                name="status"
                                                class="form-control form-control-sm d-inline-block"
                                                style="width: auto;"
                                                onchange="this.form.submit()"
                                                aria-label="Update status for {{ $user->name }}"
                                                @if ($user->id === auth()->id()) disabled @endif
                                            >
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}" @selected($user->status === $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="badge badge-{{ $user->statusBadgeClass() }}">
                                            {{ $statuses[$user->status] ?? ucfirst($user->status) }}
                                        </span>
                                    @endcan
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td class="text-right text-nowrap">
                                    @can('view', $user)
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-xs btn-primary" title="View" aria-label="View {{ $user->name }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                    @endcan
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-xs btn-default" title="Edit" aria-label="Edit {{ $user->name }}">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $user)
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="btn btn-xs btn-danger"
                                                    title="Delete"
                                                    aria-label="Delete {{ $user->name }}"
                                                    data-crm-confirm="Delete this user? This cannot be undone."
                                                    data-crm-confirm-title="Delete user"
                                                    data-crm-confirm-label="Delete"
                                                >
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="card-footer clearfix">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
