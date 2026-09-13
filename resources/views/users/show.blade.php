<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="$user->name"
            subtitle="User profile"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Users', 'url' => route('users.index')],
                ['label' => $user->name],
            ]"
        >
            <x-slot:actions>
                @can('update', $user)
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-default btn-sm">
                        <i class="fas fa-edit" aria-hidden="true"></i> Edit
                    </a>
                @endcan
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to users
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Profile details</h3>
                    @if ($user->id === auth()->id())
                        <span class="badge badge-light">You</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <x-user-avatar :user="$user" :size="64" class="mr-3" />
                        <div>
                            <div class="font-weight-bold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">
                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                        </dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $user->statusBadgeClass() }}">
                                {{ $statuses[$user->status] ?? ucfirst($user->status) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Roles</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $user->roleBadgeClass() }}">
                                {{ $user->roleNames() ?: '—' }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Joined</dt>
                        <dd class="col-sm-8">{{ $user->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>

                        <dt class="col-sm-4">Last updated</dt>
                        <dd class="col-sm-8">{{ $user->updated_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('delete', $user)
                @if ($user->id !== auth()->id())
                    <div class="card card-outline card-danger">
                        <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                            <div class="mb-2 mb-md-0 pr-2">
                                <strong class="d-block">Delete user</strong>
                                <span class="text-muted small">Permanently remove this account.</span>
                            </div>
                            <form method="POST" action="{{ route('users.destroy', $user) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="btn btn-danger btn-sm"
                                    data-crm-confirm="Delete this user? This cannot be undone."
                                    data-crm-confirm-title="Delete user"
                                    data-crm-confirm-label="Delete"
                                >
                                    <i class="fas fa-trash" aria-hidden="true"></i> Delete user
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
