<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Edit lead"
            subtitle="Update lead details and assignment."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Leads', 'url' => route('leads.index')],
                ['label' => $lead->name, 'url' => route('leads.show', $lead)],
                ['label' => 'Edit'],
            ]"
        >
            <x-slot:actions>
                <a href="{{ route('leads.show', $lead) }}" class="btn btn-default btn-sm">
                    <i class="fas fa-eye" aria-hidden="true"></i> View lead
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('leads.update', $lead) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <x-form-section title="Contact">
                    <div class="form-group">
                        <x-form-label for="name" :required="true">Name</x-form-label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $lead->name) }}" required autocomplete="name">
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="email">Email</x-form-label>
                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $lead->email) }}" autocomplete="email">
                                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="phone">Phone</x-form-label>
                                <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $lead->phone) }}" autocomplete="tel">
                                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="company">Company</x-form-label>
                        <input id="company" name="company" type="text" class="form-control @error('company') is-invalid @enderror"
                               value="{{ old('company', $lead->company) }}" autocomplete="organization">
                        @error('company')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Pipeline">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="source">Source</x-form-label>
                                <select id="source" name="source" class="form-control @error('source') is-invalid @enderror">
                                    <option value="">— Select —</option>
                                    @foreach (\App\Models\Lead::SOURCES as $source)
                                        <option value="{{ $source }}" @selected(old('source', $lead->source) === $source)>
                                            {{ ucfirst(str_replace('_', ' ', $source)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('source')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="status">Status</x-form-label>
                                <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                                    @foreach (\App\Models\Lead::manuallyAssignableStatuses($lead->status) as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', $lead->status) === $status)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @else
                                    @if ($lead->status !== 'won')
                                        <small class="form-text text-muted">To mark as won, use Convert to customer.</small>
                                    @endif
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @can('assign', $lead)
                            <div class="col-md-6">
                                <div class="form-group">
                                    <x-form-label for="assigned_to">Assign to</x-form-label>
                                    <select id="assigned_to" name="assigned_to" class="form-control @error('assigned_to') is-invalid @enderror">
                                        <option value="">— Unassigned —</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('assigned_to', $lead->assigned_to) == $user->id)>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        @else
                            <div class="col-md-6">
                                <div class="form-group">
                                    <x-form-label>Assigned to</x-form-label>
                                    <input type="text" class="form-control" value="{{ $lead->assignee?->name ?? 'Unassigned' }}" disabled>
                                </div>
                            </div>
                        @endcan
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="estimated_value">Estimated value</x-form-label>
                                <input id="estimated_value" name="estimated_value" type="number" step="0.01"
                                       class="form-control @error('estimated_value') is-invalid @enderror"
                                       value="{{ old('estimated_value', $lead->estimated_value) }}">
                                @error('estimated_value')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="follow_up_date">Follow-up date</x-form-label>
                        <input id="follow_up_date" name="follow_up_date" type="date"
                               class="form-control @error('follow_up_date') is-invalid @enderror"
                               value="{{ old('follow_up_date', $lead->follow_up_date ? \Carbon\Carbon::parse($lead->follow_up_date)->format('Y-m-d') : '') }}">
                        @error('follow_up_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Notes">
                    <div class="form-group mb-0">
                        <x-form-label for="notes">Notes</x-form-label>
                        <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ old('notes', $lead->notes) }}</textarea>
                        @error('notes')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Update lead
                    </button>
                    <a href="{{ route('leads.show', $lead) }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    @if ($lead->status !== 'won')
        @can('convert', $lead)
            <div class="card card-outline card-success mt-3">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                    <div class="mb-2 mb-md-0">
                        <strong class="d-block">Convert to customer</strong>
                        <span class="text-muted small">Marks this lead as won and creates a customer record.</span>
                    </div>
                    <form method="POST" action="{{ route('leads.convert', $lead) }}">
                        @csrf
                        <button
                            type="submit"
                            class="btn btn-success"
                            data-crm-confirm="Convert this lead to a customer? This marks the lead as won."
                            data-crm-confirm-title="Convert lead"
                            data-crm-confirm-label="Convert"
                            data-crm-confirm-class="btn-success"
                        >
                            <i class="fas fa-user-check" aria-hidden="true"></i> Convert to customer
                        </button>
                    </form>
                </div>
            </div>
        @endcan
    @endif
</x-app-layout>
