<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Create user"
            subtitle="Add a teammate, set their password, and choose a role."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Users', 'url' => route('users.index')],
                ['label' => 'Create'],
            ]"
        />
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data" id="create-user-form">
            @csrf
            <div class="card-body">
                <x-form-section title="Profile" description="Basic account details.">
                    <div class="form-group">
                        <x-form-label for="name" :required="true">Full name</x-form-label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required autocomplete="name">
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <x-form-label for="email" :required="true">Email</x-form-label>
                        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-image-crop-upload
                            name="photo"
                            id="photo"
                            label="Profile photo"
                            help="Optional. Choose a photo, then drag it to adjust inside the frame. JPEG, PNG, GIF or WebP. Max 2 MB."
                        />
                    </div>
                </x-form-section>

                <x-form-section title="Security" description="Preset an initial password for this user.">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <x-form-label for="password" :required="true" class="mb-0">Password</x-form-label>
                                    <button type="button" class="btn btn-link btn-sm p-0" id="generate-password-btn">
                                        Generate password
                                    </button>
                                </div>
                                <x-password-input
                                    name="password"
                                    id="password"
                                    autocomplete="new-password"
                                    :required="true"
                                    class="@error('password') is-invalid @enderror"
                                />
                                <small class="form-text text-muted">
                                    At least 10 characters, with upper and lower case, a number, and a symbol.
                                </small>
                                @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="password_confirmation" :required="true">Confirm password</x-form-label>
                                <x-password-input
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    autocomplete="new-password"
                                    :required="true"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-checkbox">
                            <input type="hidden" name="email_credentials" value="0">
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="email_credentials"
                                name="email_credentials"
                                value="1"
                                @checked(old('email_credentials', '1') == '1')
                            >
                            <label class="custom-control-label" for="email_credentials">
                                Email this password to the user after creation
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            They will receive their login email and temporary password so they can sign in right away.
                        </small>
                    </div>
                </x-form-section>

                <x-form-section title="Access" description="Roles and account status.">
                    <div class="form-group">
                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                            <x-form-label :required="true" class="mb-0">Roles</x-form-label>
                            @can('create', App\Models\Role::class)
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-toggle="modal"
                                    data-target="#createRoleModal"
                                >
                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                    Create role &amp; assign permissions
                                </button>
                            @endcan
                        </div>

                        <div id="user-roles-list">
                            @php
                                $defaultRoleIds = old('roles', [$roles->firstWhere('slug', 'sales')?->id]);
                            @endphp
                            @foreach ($roles as $role)
                                <div class="form-check">
                                    <input id="role-{{ $role->id }}" name="roles[]" type="checkbox"
                                           class="form-check-input @error('roles') is-invalid @enderror"
                                           value="{{ $role->id }}"
                                           @checked(in_array($role->id, array_filter($defaultRoleIds), true))>
                                    <label class="form-check-label" for="role-{{ $role->id }}">
                                        {{ $role->name }}
                                        @if ($role->description)
                                            <small class="text-muted d-block">{{ $role->description }}</small>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('roles')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="status" :required="true">Status</x-form-label>
                        <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Save user
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    @can('create', App\Models\Role::class)
        {{-- Form is the modal-content so Bootstrap scrollable flex layout can constrain .modal-body. --}}
        <div class="modal fade" id="createRoleModal" tabindex="-1" role="dialog" aria-labelledby="createRoleModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <form
                    id="create-role-modal-form"
                    class="modal-content"
                    method="POST"
                    action="{{ route('roles.store') }}"
                >
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createRoleModalTitle">Create role &amp; assign permissions</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="create-role-modal-errors" class="alert alert-danger d-none" role="alert"></div>

                        <p class="text-muted mb-3">
                            Create a new company role when none of the existing ones fit this user. It will be selected automatically below.
                        </p>

                        <div class="form-group">
                            <x-form-label for="modal_role_name" :required="true">Name</x-form-label>
                            <input id="modal_role_name" name="name" type="text" class="form-control" required maxlength="100">
                        </div>

                        <div class="form-group">
                            <x-form-label for="modal_role_slug" :required="true">Slug</x-form-label>
                            <input id="modal_role_slug" name="slug" type="text" class="form-control" required maxlength="50"
                                   pattern="[A-Za-z0-9_-]+" placeholder="e.g. support_agent">
                            <small class="form-text text-muted">Lowercase letters, numbers, dashes and underscores only.</small>
                        </div>

                        <div class="form-group">
                            <x-form-label for="modal_role_description">Description</x-form-label>
                            <textarea id="modal_role_description" name="description" rows="2" class="form-control" maxlength="1000"></textarea>
                        </div>

                        <div class="form-group mb-0">
                            <x-form-label>Permissions</x-form-label>
                            <x-permission-checklist
                                :module-permissions="$modulePermissions"
                                :selected="[]"
                                name="permissions"
                            />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="create-role-modal-submit">
                            <i class="fas fa-save" aria-hidden="true"></i> Save role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @push('js')
        <script>
            (function () {
                function generatePassword(length) {
                    length = length || 14;
                    var upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                    var lower = 'abcdefghijkmnopqrstuvwxyz';
                    var numbers = '23456789';
                    var symbols = '!@#$%&*?';
                    var all = upper + lower + numbers + symbols;
                    var chars = [
                        upper[Math.floor(Math.random() * upper.length)],
                        lower[Math.floor(Math.random() * lower.length)],
                        numbers[Math.floor(Math.random() * numbers.length)],
                        symbols[Math.floor(Math.random() * symbols.length)],
                    ];

                    if (window.crypto && window.crypto.getRandomValues) {
                        var random = new Uint32Array(length - chars.length);
                        window.crypto.getRandomValues(random);
                        for (var i = 0; i < random.length; i++) {
                            chars.push(all[random[i] % all.length]);
                        }
                    } else {
                        for (var j = chars.length; j < length; j++) {
                            chars.push(all[Math.floor(Math.random() * all.length)]);
                        }
                    }

                    for (var k = chars.length - 1; k > 0; k--) {
                        var swap = Math.floor(Math.random() * (k + 1));
                        var tmp = chars[k];
                        chars[k] = chars[swap];
                        chars[swap] = tmp;
                    }

                    return chars.join('');
                }

                function slugify(value) {
                    return String(value || '')
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '')
                        .slice(0, 50);
                }

                function setPasswordInputs(value) {
                    var password = document.getElementById('password');
                    var confirmation = document.getElementById('password_confirmation');
                    if (password) {
                        password.value = value;
                        password.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (confirmation) {
                        confirmation.value = value;
                        confirmation.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }

                var generateBtn = document.getElementById('generate-password-btn');
                if (generateBtn) {
                    generateBtn.addEventListener('click', function () {
                        setPasswordInputs(generatePassword(14));
                    });
                }

                var roleForm = document.getElementById('create-role-modal-form');
                if (!roleForm) {
                    return;
                }

                var nameInput = document.getElementById('modal_role_name');
                var slugInput = document.getElementById('modal_role_slug');
                var slugTouched = false;
                var errorBox = document.getElementById('create-role-modal-errors');
                var submitBtn = document.getElementById('create-role-modal-submit');
                var rolesList = document.getElementById('user-roles-list');

                if (slugInput) {
                    slugInput.addEventListener('input', function () {
                        slugTouched = slugInput.value.trim().length > 0;
                    });
                }

                if (nameInput && slugInput) {
                    nameInput.addEventListener('input', function () {
                        if (!slugTouched) {
                            slugInput.value = slugify(nameInput.value);
                        }
                    });
                }

                function showRoleErrors(payload) {
                    if (!errorBox) {
                        return;
                    }

                    var messages = [];
                    if (payload && payload.message) {
                        messages.push(payload.message);
                    }
                    if (payload && payload.errors) {
                        Object.keys(payload.errors).forEach(function (key) {
                            (payload.errors[key] || []).forEach(function (msg) {
                                messages.push(msg);
                            });
                        });
                    }

                    if (!messages.length) {
                        messages.push('Unable to create role. Please try again.');
                    }

                    errorBox.innerHTML = messages.map(function (msg) {
                        return '<div>' + msg + '</div>';
                    }).join('');
                    errorBox.classList.remove('d-none');
                }

                function clearRoleErrors() {
                    if (!errorBox) {
                        return;
                    }
                    errorBox.classList.add('d-none');
                    errorBox.innerHTML = '';
                }

                function appendRoleCheckbox(role) {
                    if (!rolesList || !role || !role.id) {
                        return;
                    }

                    if (document.getElementById('role-' + role.id)) {
                        document.getElementById('role-' + role.id).checked = true;
                        return;
                    }

                    var wrapper = document.createElement('div');
                    wrapper.className = 'form-check';

                    var input = document.createElement('input');
                    input.id = 'role-' + role.id;
                    input.name = 'roles[]';
                    input.type = 'checkbox';
                    input.className = 'form-check-input';
                    input.value = String(role.id);
                    input.checked = true;

                    var label = document.createElement('label');
                    label.className = 'form-check-label';
                    label.setAttribute('for', input.id);
                    label.appendChild(document.createTextNode(role.name || 'New role'));

                    if (role.description) {
                        var small = document.createElement('small');
                        small.className = 'text-muted d-block';
                        small.textContent = role.description;
                        label.appendChild(small);
                    }

                    wrapper.appendChild(input);
                    wrapper.appendChild(label);
                    rolesList.appendChild(wrapper);
                }

                roleForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    clearRoleErrors();

                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }

                    var formData = new FormData(roleForm);

                    fetch(roleForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    }).then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, status: response.status, data: data };
                        }).catch(function () {
                            return { ok: response.ok, status: response.status, data: null };
                        });
                    }).then(function (result) {
                        if (!result.ok) {
                            showRoleErrors(result.data || {});
                            return;
                        }

                        appendRoleCheckbox(result.data && result.data.role ? result.data.role : null);
                        roleForm.reset();
                        slugTouched = false;
                        clearRoleErrors();

                        if (window.jQuery) {
                            window.jQuery('#createRoleModal').modal('hide');
                        }
                    }).catch(function () {
                        showRoleErrors({ message: 'Unable to create role. Please try again.' });
                    }).finally(function () {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }
                    });
                });
            })();
        </script>
    @endpush
</x-app-layout>
