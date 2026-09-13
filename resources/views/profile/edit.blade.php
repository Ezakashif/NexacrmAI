<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Profile"
            subtitle="Your account details and security settings."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Profile'],
            ]"
        />
    </x-slot>

    @if ($errors->any() && $errors->default->isNotEmpty())
        <div class="alert alert-danger alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            {{ $errors->first() }}
        </div>
    @endif

    @error('sessions')
        <div class="alert alert-danger alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            {{ $message }}
        </div>
    @enderror

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card card-outline card-primary">
                <div class="card-body text-center">
                    <x-form-section title="Profile photo" description="Shown across the CRM next to your name.">
                        <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="text-left">
                            @csrf
                            @method('patch')
                            <x-image-crop-upload
                                name="photo"
                                id="photo"
                                label="Upload new photo"
                                :preview-url="$user->photo_path ? $user->photoUrl() : null"
                                :required="true"
                                help="Choose a photo, then drag it to adjust inside the frame. JPEG, PNG, GIF or WebP. Max 2 MB."
                            />
                            <button type="submit" class="btn btn-primary btn-sm mt-3">
                                <i class="fas fa-upload" aria-hidden="true"></i> Upload photo
                            </button>
                        </form>
                        @if ($user->photo_path)
                            <form method="post" action="{{ route('profile.photo.destroy') }}" class="mt-2">
                                @csrf
                                @method('delete')
                                <button
                                    type="submit"
                                    class="btn btn-outline-danger btn-sm"
                                    data-crm-confirm="Remove your profile photo? Your initials will be shown instead."
                                    data-crm-confirm-title="Remove photo"
                                    data-crm-confirm-label="Remove"
                                >
                                    <i class="fas fa-trash" aria-hidden="true"></i> Remove photo
                                </button>
                            </form>
                        @endif
                    </x-form-section>
                </div>
            </div>

            <div class="card card-outline card-secondary mt-3">
                <div class="card-body">
                    <x-form-section title="Last login" description="Most recent successful sign-in.">
                        <dl class="mb-0 small">
                            <div class="d-flex justify-content-between py-1">
                                <dt class="text-muted mb-0">When</dt>
                                <dd class="mb-0 font-weight-bold">
                                    {{ $user->last_login_at?->timezone($user->timezone ?: config('app.timezone'))->format('M j, Y g:i A') ?? 'Never' }}
                                </dd>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <dt class="text-muted mb-0">IP address</dt>
                                <dd class="mb-0 font-weight-bold">{{ $user->last_login_ip ?: '—' }}</dd>
                            </div>
                        </dl>
                    </x-form-section>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-outline card-primary mb-3">
                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')
                    <div class="card-body">
                        <x-form-section
                            title="Profile information"
                            description="Update your contact details and preferences."
                        >
                            <div class="form-group">
                                <x-form-label for="name" :required="true">Name</x-form-label>
                                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user->name) }}" required autocomplete="name">
                                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <x-form-label for="email" :required="true">Email</x-form-label>
                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}" required autocomplete="username"
                                       @if (! empty($isDemoUser)) readonly @endif>
                                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <x-form-label for="phone">Phone</x-form-label>
                                <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $user->phone) }}" autocomplete="tel" placeholder="+1 555 000 0000">
                                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <x-form-label for="timezone">Timezone</x-form-label>
                                    <select id="timezone" name="timezone" class="form-control @error('timezone') is-invalid @enderror">
                                        <option value="">Use company / platform default</option>
                                        @foreach ($timezones as $timezone)
                                            <option value="{{ $timezone }}" @selected(old('timezone', $user->timezone) === $timezone)>
                                                {{ $timezone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('timezone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <x-form-label for="language">Language</x-form-label>
                                    <select id="language" name="language" class="form-control @error('language') is-invalid @enderror">
                                        <option value="">Default</option>
                                        @foreach ($languages as $code => $label)
                                            <option value="{{ $code }}" @selected(old('language', $user->language) === $code)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Stored for future localization.</small>
                                    @error('language')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <div class="alert alert-warning crm-keep-alert mt-3 mb-0">
                                    Your email is unverified.
                                    <button form="send-verification" type="submit" class="btn btn-link btn-sm p-0 align-baseline">
                                        Resend verification email
                                    </button>
                                    @if (session('verification_preview_url'))
                                        <div class="mt-2 mb-0">
                                            Mail delivery may be disabled locally.
                                            <a href="{{ session('verification_preview_url') }}">Open verification preview link</a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </x-form-section>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                    </form>
                @endif
            </div>

            <div class="card card-outline card-info mb-3">
                <form method="post" action="{{ route('profile.notification-preferences.update') }}">
                    @csrf
                    @method('patch')
                    <div class="card-body">
                        <x-form-section
                            title="Notification preferences"
                            description="Choose how you receive CRM notifications for in-app and email delivery."
                        >
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Notification</th>
                                            @foreach ($notificationPreferenceChannels as $channel => $label)
                                                <th scope="col" class="text-center">{{ $label }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($notificationPreferenceTypes as $typeKey => $type)
                                            <tr>
                                                <th scope="row">
                                                    <div>{{ $type['label'] }}</div>
                                                    <small id="notification-{{ $typeKey }}-description" class="text-muted font-weight-normal">
                                                        {{ $type['description'] }}
                                                    </small>
                                                </th>
                                                @foreach ($notificationPreferenceChannels as $channel => $label)
                                                    @php($inputId = 'notification-'.$typeKey.'-'.$channel)
                                                    <td class="text-center align-middle">
                                                        <input type="hidden" name="preferences[{{ $typeKey }}][{{ $channel }}]" value="0">
                                                        <div class="custom-control custom-switch d-inline-block">
                                                            <input
                                                                id="{{ $inputId }}"
                                                                name="preferences[{{ $typeKey }}][{{ $channel }}]"
                                                                type="checkbox"
                                                                value="1"
                                                                class="custom-control-input"
                                                                aria-describedby="notification-{{ $typeKey }}-description"
                                                                @checked(old('preferences.'.$typeKey.'.'.$channel, data_get($notificationPreferences, $typeKey.'.'.$channel)))
                                                            >
                                                            <label class="custom-control-label" for="{{ $inputId }}">
                                                                <span class="sr-only">{{ $label }} notifications for {{ $type['label'] }}</span>
                                                            </label>
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @error('preferences')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </x-form-section>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save notification preferences</button>
                    </div>
                </form>
            </div>

            @unless (! empty($isDemoUser))
            <div class="card card-outline card-secondary mb-3">
                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')
                    <div class="card-body">
                        <x-form-section
                            title="Update password"
                            description="Use a long, unique password. Other devices will be signed out after a successful change."
                        >
                            <div class="form-group">
                                <x-form-label for="update_password_current_password" :required="true">Current password</x-form-label>
                                <x-password-input
                                    name="current_password"
                                    id="update_password_current_password"
                                    autocomplete="current-password"
                                    :required="true"
                                    class="@error('current_password', 'updatePassword') is-invalid @enderror"
                                />
                                @error('current_password', 'updatePassword')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <x-form-label for="update_password_password" :required="true">New password</x-form-label>
                                <x-password-input
                                    name="password"
                                    id="update_password_password"
                                    autocomplete="new-password"
                                    :required="true"
                                    class="@error('password', 'updatePassword') is-invalid @enderror"
                                />
                                <small class="form-text text-muted">
                                    At least 10 characters, with upper and lower case, a number, and a symbol.
                                </small>
                                @error('password', 'updatePassword')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group mb-0">
                                <x-form-label for="update_password_password_confirmation" :required="true">Confirm password</x-form-label>
                                <x-password-input
                                    name="password_confirmation"
                                    id="update_password_password_confirmation"
                                    autocomplete="new-password"
                                    :required="true"
                                />
                            </div>
                        </x-form-section>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update password</button>
                    </div>
                </form>
            </div>
            @endunless

            <div class="card card-outline card-info mb-3">
                <div class="card-body">
                    <x-form-section
                        title="Active sessions"
                        description="Devices currently signed in to your account."
                    >
                        @if (count($sessions) === 0)
                            <p class="text-muted mb-0 small">No active sessions were found.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-3">
                                    <thead>
                                        <tr>
                                            <th>Device</th>
                                            <th>IP</th>
                                            <th>Last active</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sessions as $session)
                                            <tr>
                                                <td>
                                                    <div class="font-weight-bold">{{ $session['device_label'] }}</div>
                                                    @if ($session['is_current'])
                                                        <span class="badge badge-success">This device</span>
                                                    @endif
                                                </td>
                                                <td class="text-muted">{{ $session['ip_address'] ?: '—' }}</td>
                                                <td class="text-muted">{{ $session['last_activity_at']->diffForHumans() }}</td>
                                                <td class="text-right">
                                                    @if (! $session['is_current'])
                                                        <form method="post" action="{{ route('profile.sessions.destroy', $session['id']) }}" class="d-inline">
                                                            @csrf
                                                            @method('delete')
                                                            <button
                                                                type="submit"
                                                                class="btn btn-outline-danger btn-xs"
                                                                data-crm-confirm="Sign out this device?"
                                                                data-crm-confirm-title="Revoke session"
                                                                data-crm-confirm-label="Sign out"
                                                            >
                                                                Revoke
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted small">Current</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if (collect($sessions)->contains(fn ($session) => ! $session['is_current']))
                                <form method="post" action="{{ route('profile.sessions.destroy-others') }}">
                                    @csrf
                                    @method('delete')
                                    <button
                                        type="submit"
                                        class="btn btn-outline-secondary btn-sm"
                                        data-crm-confirm="Sign out every other device? You will stay signed in here."
                                        data-crm-confirm-title="Sign out other devices"
                                        data-crm-confirm-label="Sign out others"
                                    >
                                        Sign out other devices
                                    </button>
                                </form>
                            @endif
                        @endif
                    </x-form-section>
                </div>
            </div>

            @unless (! empty($isDemoUser))
            <div class="card card-outline card-danger">
                <div class="card-body">
                    <x-form-section
                        title="Delete account"
                        description="Once your account is deleted, all of its resources and data will be permanently deleted."
                    >
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteAccountModal">
                            Delete account
                        </button>
                    </x-form-section>
                </div>
            </div>
            @endunless
        </div>
    </div>

    @unless (! empty($isDemoUser))
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="deleteAccountModalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="post" action="{{ route('profile.destroy') }}" class="modal-content">
                @csrf
                @method('delete')
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalTitle">Delete account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Enter your password to confirm you want to permanently delete your account.</p>
                    <div class="form-group mb-0">
                        <x-form-label for="password" :required="true">Password</x-form-label>
                        <x-password-input
                            name="password"
                            id="password"
                            autocomplete="current-password"
                            :required="true"
                            class="@error('password', 'userDeletion') is-invalid @enderror"
                        />
                        @error('password', 'userDeletion')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete account</button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->userDeletion->isNotEmpty())
        @push('js')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    $('#deleteAccountModal').modal('show');
                });
            </script>
        @endpush
    @endif
    @endunless
</x-app-layout>
