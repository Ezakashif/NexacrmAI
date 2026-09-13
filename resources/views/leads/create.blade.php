<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Create lead"
            subtitle="Add a new prospect to the pipeline."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Leads', 'url' => route('leads.index')],
                ['label' => 'Create'],
            ]"
        />
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('leads.store') }}">
            @csrf
            <input type="hidden" name="status" value="new">
            <div class="card-body">
                <x-form-section title="Contact" description="Who is this lead?">
                    <div class="form-group">
                        <x-form-label for="name" :required="true">Name</x-form-label>
                        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required autocomplete="name">
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="email">Email</x-form-label>
                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" autocomplete="email">
                                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="phone">Phone</x-form-label>
                                <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" autocomplete="tel">
                                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="company">Company</x-form-label>
                        <input id="company" name="company" type="text" class="form-control @error('company') is-invalid @enderror"
                               value="{{ old('company') }}" autocomplete="organization">
                        @error('company')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Pipeline" description="Source, ownership, and value.">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="source">Source</x-form-label>
                                <select id="source" name="source" class="form-control @error('source') is-invalid @enderror">
                                    <option value="">— Select —</option>
                                    @foreach (['website', 'facebook', 'referral', 'whatsapp', 'linkedin', 'cold_call'] as $source)
                                        <option value="{{ $source }}" @selected(old('source') === $source)>
                                            {{ ucfirst(str_replace('_', ' ', $source)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('source')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        @if (auth()->user()->canAssignLeads())
                            <div class="col-md-6">
                                <div class="form-group">
                                    <x-form-label for="assigned_to">Assign to</x-form-label>
                                    <select id="assigned_to" name="assigned_to" class="form-control @error('assigned_to') is-invalid @enderror">
                                        <option value="">— Unassigned —</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('assigned_to') == $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        @else
                            <div class="col-md-6">
                                <div class="form-group">
                                    <x-form-label>Assign to</x-form-label>
                                    <input type="text" class="form-control" value="{{ auth()->user()->name }} (you)" disabled>
                                    <small class="form-text text-muted">This lead will be assigned to you automatically.</small>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <x-form-label for="estimated_value">Estimated value</x-form-label>
                                <input id="estimated_value" name="estimated_value" type="number" step="0.01"
                                       class="form-control @error('estimated_value') is-invalid @enderror"
                                       value="{{ old('estimated_value') }}">
                                @error('estimated_value')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <x-form-label for="follow_up_date">Follow-up date</x-form-label>
                                <input id="follow_up_date" name="follow_up_date" type="date"
                                       class="form-control @error('follow_up_date') is-invalid @enderror"
                                       value="{{ old('follow_up_date') }}">
                                @error('follow_up_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </x-form-section>

                <x-form-section title="Notes" description="Optional context for your team.">
                    <div class="form-group mb-0">
                        <x-form-label for="notes">Notes</x-form-label>
                        <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4">{{ old('notes') }}</textarea>
                        @error('notes')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Save lead
                    </button>
                    <a href="{{ route('leads.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
