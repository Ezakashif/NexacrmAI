<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Create customer"
            subtitle="Add a customer account."
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => 'Customers', 'url' => route('customers.index')],
                ['label' => 'Create'],
            ]"
        />
    </x-slot>

    <div class="card card-outline card-primary">
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            <div class="card-body">
                <x-form-section title="Contact" description="Who is this customer?">
                    <div class="form-group">
                        <x-form-label for="name" :required="true">Customer name</x-form-label>
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
                            <div class="form-group mb-0">
                                <x-form-label for="phone">Phone</x-form-label>
                                <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone') }}" autocomplete="tel">
                                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </x-form-section>

                <x-form-section title="Company" description="Optional organization details.">
                    <div class="form-group">
                        <x-form-label for="company_name">Company name</x-form-label>
                        <input id="company_name" name="company_name" type="text"
                               class="form-control @error('company_name') is-invalid @enderror"
                               value="{{ old('company_name') }}" autocomplete="organization">
                        @error('company_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <x-form-label for="address">Address</x-form-label>
                        <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror"
                                  rows="3">{{ old('address') }}</textarea>
                        @error('address')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>

                <x-form-section title="Notes">
                    <div class="form-group mb-0">
                        <x-form-label for="notes">Notes</x-form-label>
                        <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror"
                                  rows="3">{{ old('notes') }}</textarea>
                        @error('notes')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </x-form-section>
            </div>

            <div class="card-footer">
                <div class="crm-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save" aria-hidden="true"></i> Save customer
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
