@props([
    'modulePermissions',
    'selected' => [],
    'name' => 'permissions',
])

<div class="permission-checklist">
    @foreach ($modulePermissions as $moduleKey => $module)
        <div class="card card-outline card-secondary mb-3 permission-checklist__module">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <strong class="permission-checklist__module-title">{{ $module['label'] }}</strong>
                <span class="badge badge-light">{{ count($module['permissions']) }}</span>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    @foreach ($module['permissions'] as $permission)
                        <div class="col-md-6 col-lg-3 mb-2">
                            <div class="form-check permission-checklist__item">
                                <input id="{{ $name }}-{{ $permission->id }}"
                                       name="{{ $name }}[]"
                                       type="checkbox"
                                       class="form-check-input @error($name) is-invalid @enderror"
                                       value="{{ $permission->id }}"
                                       @checked(in_array($permission->id, $selected, true))>
                                <label class="form-check-label" for="{{ $name }}-{{ $permission->id }}">
                                    {{ $permission->name }}
                                    <small class="text-muted d-block">{{ $permission->slug }}</small>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
    @error($name)<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</div>
