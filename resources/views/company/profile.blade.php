<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Company profile"
            subtitle="Overview of your workspace identity and regional defaults."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Company profile'],
            ]"
        >
            <x-slot name="actions">
                <a href="{{ route('company.settings.edit') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-cog" aria-hidden="true"></i> Edit settings
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card card-outline card-primary h-100">
                <div class="card-body text-center">
                    @if ($company->logoUrl())
                        <img
                            src="{{ $company->logoUrl() }}"
                            alt="{{ $company->name }} logo"
                            class="img-fluid rounded border bg-white p-2 mb-3"
                            style="max-height: 140px; max-width: 100%; object-fit: contain;"
                        >
                    @else
                        <div class="d-flex align-items-center justify-content-center rounded border bg-light text-muted mx-auto mb-3" style="height: 140px; max-width: 100%;">
                            <div>
                                <i class="fas fa-building fa-2x mb-2 d-block" aria-hidden="true"></i>
                                <span class="small">No logo uploaded</span>
                            </div>
                        </div>
                    @endif

                    <h2 class="h4 mb-1">{{ $company->name }}</h2>
                    <p class="text-muted small mb-3">{{ $company->slug }}</p>

                    <div class="d-flex justify-content-center flex-wrap" style="gap: 0.35rem;">
                        <span class="badge badge-{{ $company->isActive() ? 'success' : 'secondary' }}">
                            {{ \App\Models\Company::STATUSES[$company->status] ?? ucfirst($company->status) }}
                        </span>
                        <span class="badge badge-info">
                            {{ \App\Models\Company::SUBSCRIPTION_STATUSES[$company->subscription_status] ?? ucfirst((string) $company->subscription_status) }}
                        </span>
                        @if ($company->plan)
                            <span class="badge badge-light border">{{ $company->plan->name }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="card card-outline card-primary mb-3">
                <div class="card-body">
                    <x-form-section title="Contact" description="How people reach this company.">
                        <dl class="row mb-0">
                            <dt class="col-sm-3 text-muted">Email</dt>
                            <dd class="col-sm-9">{{ $company->email ?: '—' }}</dd>

                            <dt class="col-sm-3 text-muted">Phone</dt>
                            <dd class="col-sm-9">{{ $company->phone ?: '—' }}</dd>

                            <dt class="col-sm-3 text-muted">Owner</dt>
                            <dd class="col-sm-9">
                                {{ $company->owner?->name ?: '—' }}
                                @if ($company->owner?->email)
                                    <span class="text-muted small">({{ $company->owner->email }})</span>
                                @endif
                            </dd>
                        </dl>
                    </x-form-section>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <x-form-section title="Address" description="Office location on file for this workspace.">
                        @php
                            $addressLines = array_values(array_filter([
                                $company->address_line_1,
                                $company->address_line_2,
                                collect([$company->city, $company->state, $company->postal_code])->filter()->implode(', '),
                                $company->country,
                            ], fn ($line) => filled($line)));
                        @endphp

                        @if ($addressLines === [])
                            <p class="text-muted mb-0">No address on file.</p>
                        @else
                            <address class="mb-0">
                                @foreach ($addressLines as $line)
                                    <div>{{ $line }}</div>
                                @endforeach
                            </address>
                        @endif
                    </x-form-section>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <x-form-section title="Regional defaults" description="Timezone and currency used across this workspace.">
                        <dl class="row mb-0">
                            <dt class="col-sm-3 text-muted">Timezone</dt>
                            <dd class="col-sm-9">{{ $company->timezone ?: '—' }}</dd>

                            <dt class="col-sm-3 text-muted">Currency</dt>
                            <dd class="col-sm-9">{{ $company->currency ?: '—' }}</dd>

                            <dt class="col-sm-3 text-muted">Trial ends</dt>
                            <dd class="col-sm-9">{{ $company->trial_ends_at?->timezone($company->timezone ?: config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}</dd>
                        </dl>
                    </x-form-section>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-body">
                    <x-form-section title="Business hours" description="Weekly hours configured for this company.">
                        @php
                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            $hours = $company->business_hours ?? [];
                            $hasHours = collect($days)->contains(fn ($day) => filled($hours[$day] ?? null));
                        @endphp

                        @if (! $hasHours)
                            <p class="text-muted mb-0">No business hours configured.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        @foreach ($days as $day)
                                            <tr>
                                                <th class="text-muted font-weight-normal" style="width: 8rem;">{{ ucfirst($day) }}</th>
                                                <td>{{ filled($hours[$day] ?? null) ? $hours[$day] : 'Closed' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-form-section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
