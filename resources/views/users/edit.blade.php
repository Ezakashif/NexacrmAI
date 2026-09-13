<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Edit user"
            subtitle="Update account details and access."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Users', 'url' => route('users.index')],
                ['label' => $user->name, 'url' => route('users.show', $user)],
                ['label' => 'Edit'],
            ]"
        >
            <x-slot:actions>
                <a href="{{ route('users.show', $user) }}" class="btn btn-default btn-sm">
                    <i class="fas fa-eye" aria-hidden="true"></i> View user
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                <x-form-section title="Profile">
                    <div class="form-group mb-4">
                        <x-image-crop-upload
                            name="photo"
                            id="photo"
                            label="Profile photo"
                            :preview-url="$user->photo_path ? $user->photoUrl() : null"
                            help="Optional. Choose a photo, then drag it to adjust inside the frame. Max 2 MB."
                        />
                    </div>

                    <div class="form-group">
                        <x-form-label for="name" :required="true">Full name</x-form-label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="email" :required="true">Email</x-form-label>
                        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required autocomplete="email">
                        @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Security" description="Leave password blank to keep the current one.">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="password">New password</x-form-label>
                                <x-password-input
                                    name="password"
                                    id="password"
                                    autocomplete="new-password"
                                    class="@error('password') is-invalid @enderror"
                                />
                                @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <x-form-label for="password_confirmation">Confirm new password</x-form-label>
                                <x-password-input
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>
                    </div>
                </x-form-section>

                <x-form-section title="Access">
                    <div class="form-group">
                        <x-form-label :required="true">Roles</x-form-label>
                        @foreach ($roles as $role)
                            <div class="form-check">
                                <input id="role-{{ $role->id }}" name="roles[]" type="checkbox"
                                       class="form-check-input @error('roles') is-invalid @enderror"
                                       value="{{ $role->id }}"
                                       @checked(in_array($role->id, old('roles', $user->roles->pluck('id')->all()), true))
                                       @if ($user->id === auth()->id()) disabled @endif>
                                <label class="form-check-label" for="role-{{ $role->id }}">
                                    {{ $role->name }}
                                    @if ($role->description)
                                        <small class="text-muted d-block">{{ $role->description }}</small>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                        @if ($user->id === auth()->id())
                            @foreach ($user->roles as $role)
                                <input type="hidden" name="roles[]" value="{{ $role->id }}">
                            @endforeach
                            <small class="form-text text-muted">You cannot change your own roles.</small>
                        @endif
                        @error('roles')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="status" :required="true">Status</x-form-label>
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required
                                @if ($user->id === auth()->id()) disabled @endif>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $user->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($user->id === auth()->id())
                            <input type="hidden" name="status" value="active">
                            <small class="form-text text-muted">You cannot change your own status.</small>
                        @endif
                        @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Update user
                    </button>
                    <a href="{{ route('users.show', $user) }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
