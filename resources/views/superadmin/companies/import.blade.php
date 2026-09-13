@extends('superadmin.layout')

@section('title', 'Import companies')
@section('heading', 'Import companies')
@section('subheading', 'Upload a CSV to provision multiple tenants')

@section('content')
<div class="row">
    <div class="col-lg-7">
        <div class="sa-card">
            <form method="POST" action="{{ route('superadmin.companies.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="csv_file">CSV File</label>
                    <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv"
                           class="form-control-file @error('csv_file') is-invalid @enderror" required>
                    @error('csv_file')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                    <small class="sa-muted d-block mt-2">
                        Max 2 MB · up to {{ $maxRows }} data rows · duplicate slugs/emails are skipped.
                    </small>
                </div>
                <button type="submit" class="btn btn-info mr-2">Import CSV</button>
                <a href="{{ route('superadmin.companies.index') }}" class="btn btn-outline-light">Cancel</a>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="sa-card">
            <h2 class="h6 text-white mb-3">Sample CSV</h2>
            <p class="sa-muted mb-2">Required header columns:</p>
            <code class="d-block mb-3" style="white-space: normal; color:#e5e7eb;">{{ implode(', ', $headers) }}</code>
            <a href="{{ route('superadmin.companies.import.sample') }}" class="btn btn-sm btn-outline-light">
                Download sample CSV
            </a>
            <p class="sa-muted small mt-3 mb-0">
                Optional admin columns provision the first company administrator and sync default roles.
            </p>
        </div>
    </div>
</div>
@endsection
