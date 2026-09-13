<x-app-layout>
    <x-slot name="header">
        <x-page-header
            :title="'Import '.$label"
            :subtitle="'Upload a CSV file to create multiple '.strtolower($label).'.'"
            :breadcrumbs="[
                ['label' => 'Home', 'url' => route('dashboard')],
                ['label' => $label, 'url' => route($indexRoute)],
                ['label' => 'Import'],
            ]"
        >
            <x-slot:actions>
                <a href="{{ route($indexRoute) }}" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to {{ strtolower($label) }}
                </a>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible crm-keep-alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <form method="POST" action="{{ route('imports.store', $type) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <x-form-section
                            title="CSV upload"
                            description="Use the sample file for the expected columns, then upload your sheet as UTF-8 CSV."
                        >
                            <div class="form-group mb-0">
                                <x-form-label for="csv_file" :required="true">CSV file</x-form-label>
                                <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv"
                                       class="form-control-file @error('csv_file') is-invalid @enderror" required>
                                @error('csv_file')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">
                                    Max 2 MB · up to {{ $maxRows }} data rows · invalid and duplicate emails are skipped.
                                    Tip: in Excel use <strong>Save As → CSV UTF-8</strong>. Avoid email hyperlinks — paste emails as plain text.
                                </small>
                            </div>
                        </x-form-section>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-upload" aria-hidden="true"></i> Import CSV
                        </button>
                        <a href="{{ route($indexRoute) }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <x-form-section title="Sample CSV" description="Required header columns for this import type.">
                        <code class="d-block mb-3 text-wrap">{{ implode(', ', $headers) }}</code>
                        <a href="{{ route('imports.sample', $type) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download" aria-hidden="true"></i> Download sample CSV
                        </a>
                    </x-form-section>
                </div>
            </div>

            @if ($type === 'leads')
                <div class="alert alert-info crm-keep-alert mb-0">
                    New leads are created with status <strong>new</strong>.
                    @can('assign.leads')
                        Optional <code>assigned_to</code> accepts an existing user email.
                    @else
                        Leads will be assigned to you automatically.
                    @endcan
                </div>
            @elseif ($type === 'users')
                <div class="alert alert-info crm-keep-alert mb-0">
                    <code>roles</code> accepts role slugs such as <code>sales</code> (comma-separated for multiple).
                    Password must meet the app password policy.
                </div>
            @elseif ($type === 'customers')
                <div class="alert alert-info crm-keep-alert mb-0">
                    Duplicate emails are skipped. Review the sample CSV for required and optional columns.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
