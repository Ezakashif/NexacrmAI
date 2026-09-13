<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Company settings"
            subtitle="Manage your workspace identity, address, and business hours."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Company settings'],
            ]"
        />
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('company.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card card-outline card-primary h-100">
                    <div class="card-body">
                        <x-form-section title="Company logo" description="Shown in the CRM for your workspace.">
                            <div class="text-center mb-3">
                                @if ($company->logoUrl())
                                    <img
                                        src="{{ $company->logoUrl() }}"
                                        alt="{{ $company->name }} logo"
                                        class="img-fluid rounded border bg-white p-2"
                                        style="max-height: 120px; max-width: 100%; object-fit: contain;"
                                    >
                                @else
                                    <div class="d-flex align-items-center justify-content-center rounded border bg-light text-muted mx-auto" style="height: 120px; max-width: 100%;">
                                        <div>
                                            <i class="fas fa-building fa-2x mb-2 d-block" aria-hidden="true"></i>
                                            <span class="small">No logo uploaded</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="form-group mb-0">
                                <x-form-label for="logo">Upload logo</x-form-label>
                                <div class="custom-file">
                                    <input
                                        type="file"
                                        class="custom-file-input @error('logo') is-invalid @enderror"
                                        id="logo"
                                        name="logo"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                    >
                                    <label class="custom-file-label" for="logo">Choose image…</label>
                                </div>
                                <small class="form-text text-muted">JPEG, PNG, GIF, or WebP. Max 2 MB.</small>
                                @error('logo')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>

                            @if ($company->logoUrl())
                                <div class="custom-control custom-checkbox mt-3">
                                    <input type="checkbox" class="custom-control-input" id="remove_logo" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                    <label class="custom-control-label text-danger" for="remove_logo">Remove current logo</label>
                                </div>
                            @endif
                        </x-form-section>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3">
                <div class="card card-outline card-primary mb-3">
                    <div class="card-body">
                        <x-form-section
                            title="Company profile"
                            description="Basic contact details for your workspace."
                        >
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <x-form-label for="name" :required="true">Company name</x-form-label>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $company->name) }}"
                                        required
                                        autocomplete="organization"
                                    >
                                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <x-form-label for="email">Email</x-form-label>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $company->email) }}"
                                        autocomplete="email"
                                        placeholder="hello@company.com"
                                    >
                                    @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-6 mb-0">
                                    <x-form-label for="phone">Phone</x-form-label>
                                    <input
                                        id="phone"
                                        name="phone"
                                        type="text"
                                        class="form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone', $company->phone) }}"
                                        autocomplete="tel"
                                        placeholder="+1 555 000 0000"
                                    >
                                    @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </x-form-section>
                    </div>
                </div>

                <div class="card card-outline card-secondary mb-3">
                    <div class="card-body">
                        <x-form-section
                            title="Address"
                            description="Optional. Leave blank if you do not want an office address on file."
                        >
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <x-form-label for="address_line_1">Address line 1</x-form-label>
                                    <input
                                        id="address_line_1"
                                        name="address_line_1"
                                        type="text"
                                        class="form-control @error('address_line_1') is-invalid @enderror"
                                        value="{{ old('address_line_1', $company->address_line_1) }}"
                                        autocomplete="address-line1"
                                    >
                                    @error('address_line_1')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <x-form-label for="address_line_2">Address line 2</x-form-label>
                                    <input
                                        id="address_line_2"
                                        name="address_line_2"
                                        type="text"
                                        class="form-control @error('address_line_2') is-invalid @enderror"
                                        value="{{ old('address_line_2', $company->address_line_2) }}"
                                        autocomplete="address-line2"
                                    >
                                    @error('address_line_2')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <x-form-label for="city">City</x-form-label>
                                    <input
                                        id="city"
                                        name="city"
                                        type="text"
                                        class="form-control @error('city') is-invalid @enderror"
                                        value="{{ old('city', $company->city) }}"
                                        autocomplete="address-level2"
                                    >
                                    @error('city')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <x-form-label for="state">State / region</x-form-label>
                                    <input
                                        id="state"
                                        name="state"
                                        type="text"
                                        class="form-control @error('state') is-invalid @enderror"
                                        value="{{ old('state', $company->state) }}"
                                        autocomplete="address-level1"
                                    >
                                    @error('state')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <x-form-label for="postal_code">Postal code</x-form-label>
                                    <input
                                        id="postal_code"
                                        name="postal_code"
                                        type="text"
                                        class="form-control @error('postal_code') is-invalid @enderror"
                                        value="{{ old('postal_code', $company->postal_code) }}"
                                        autocomplete="postal-code"
                                    >
                                    @error('postal_code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-4 mb-0">
                                    <x-form-label for="country">Country code</x-form-label>
                                    <input
                                        id="country"
                                        name="country"
                                        type="text"
                                        class="form-control text-uppercase @error('country') is-invalid @enderror"
                                        value="{{ old('country', $company->country) }}"
                                        maxlength="2"
                                        placeholder="US"
                                        autocomplete="country"
                                    >
                                    <small class="form-text text-muted">ISO 2-letter code (e.g. US, GB).</small>
                                    @error('country')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </x-form-section>
                    </div>
                </div>

                <div class="card card-outline card-secondary">
                    <div class="card-body">
                        <x-form-section
                            title="Regional defaults"
                            description="Used for dates, schedules, and money formatting in this workspace."
                        >
                            <div class="form-row mb-0">
                                <div class="form-group col-md-8">
                                    <x-form-label for="timezone">Timezone</x-form-label>
                                    <select
                                        id="timezone"
                                        name="timezone"
                                        class="form-control @error('timezone') is-invalid @enderror"
                                    >
                                        <option value="">Use platform default</option>
                                        @foreach ($timezones as $timezone)
                                            <option value="{{ $timezone }}" @selected(old('timezone', $company->timezone) === $timezone)>
                                                {{ $timezone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('timezone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-4 mb-0">
                                    <x-form-label for="currency">Currency</x-form-label>
                                    <input
                                        id="currency"
                                        name="currency"
                                        type="text"
                                        class="form-control text-uppercase @error('currency') is-invalid @enderror"
                                        value="{{ old('currency', $company->currency) }}"
                                        maxlength="3"
                                        placeholder="USD"
                                    >
                                    <small class="form-text text-muted">3-letter code (e.g. USD).</small>
                                    @error('currency')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </x-form-section>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-secondary mb-3">
            <div class="card-body">
                <x-form-section
                    title="Business hours"
                    description="Enter hours such as 09:00–17:00, or leave a day blank when closed."
                >
                    <div class="form-row">
                        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                            <div class="form-group col-md-6 col-xl-4 {{ $loop->last ? 'mb-0' : '' }}">
                                <x-form-label :for="'business_hours_'.$day">{{ ucfirst($day) }}</x-form-label>
                                <input
                                    id="business_hours_{{ $day }}"
                                    name="business_hours[{{ $day }}]"
                                    type="text"
                                    class="form-control @error('business_hours.'.$day) is-invalid @enderror"
                                    value="{{ old('business_hours.'.$day, $company->business_hours[$day] ?? '') }}"
                                    placeholder="09:00–17:00"
                                >
                                @error('business_hours.'.$day)<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                </x-form-section>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
                <span class="text-muted small mb-2 mb-md-0">Changes apply immediately for everyone in this company.</span>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save" aria-hidden="true"></i> Save company settings
                </button>
            </div>
        </div>
    </form>

    @push('js')
        <script>
            document.getElementById('logo')?.addEventListener('change', function () {
                const label = this.nextElementSibling;
                if (label && this.files?.[0]) {
                    label.textContent = this.files[0].name;
                }
            });
        </script>
    @endpush
</x-app-layout>
