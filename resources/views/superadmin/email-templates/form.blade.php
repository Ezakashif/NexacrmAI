@extends('superadmin.layout')

@section('title', $mode === 'create' ? 'New Email Template' : 'Edit Email Template')
@section('heading', $mode === 'create' ? 'New Email Template' : $template->name)
@section('subheading', $mode === 'create' ? 'Create a localized transactional email template' : $template->categoryLabel().' · '.strtoupper($template->locale))

@section('content')
@php
    $categoryKey = old('category', $template->category);
    $placeholderHelp = $categoryKey
        ? (config('email_templates.categories.'.$categoryKey.'.placeholders') ?? [])
        : [];
@endphp

<div class="sa-card">
    <form method="POST"
          action="{{ $mode === 'create' ? route('superadmin.email-templates.store') : route('superadmin.email-templates.update', $template) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="form-row">
            <div class="form-group col-md-4">
                <label class="sa-muted">Category</label>
                @if ($mode === 'create')
                    <select name="category" class="custom-select" required>
                        <option value="">Select…</option>
                        @foreach ($categories as $key => $meta)
                            <option value="{{ $key }}" @selected(old('category', $template->category) === $key)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control" value="{{ $template->categoryLabel() }}" disabled>
                @endif
            </div>
            <div class="form-group col-md-4">
                <label class="sa-muted">Locale</label>
                <select name="locale" class="custom-select" required>
                    @foreach ($locales as $code => $label)
                        <option value="{{ $code }}" @selected(old('locale', $template->locale) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label class="sa-muted">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
            </div>
        </div>

        <div class="form-group">
            <label class="sa-muted">Subject</label>
            <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}" required>
            <small class="sa-muted">Use placeholders like <code>{{'{{user_name}}'}}</code>.</small>
        </div>

        <div class="form-group">
            <label class="sa-muted">HTML body</label>
            <textarea name="html_body" class="form-control" rows="12" required>{{ old('html_body', $template->html_body) }}</textarea>
        </div>

        <div class="form-group">
            <label class="sa-muted">Plain text body</label>
            <textarea name="text_body" class="form-control" rows="8">{{ old('text_body', $template->text_body) }}</textarea>
        </div>

        <div class="form-row">
            <div class="form-group col-md-3">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $template->is_active))>
                    <label class="custom-control-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="form-group col-md-3">
                <div class="custom-control custom-switch">
                    <input type="hidden" name="use_branding" value="0">
                    <input type="checkbox" class="custom-control-input" id="use_branding" name="use_branding" value="1" @checked(old('use_branding', $template->use_branding))>
                    <label class="custom-control-label" for="use_branding">Use platform branding</label>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap" style="gap:0.5rem;">
            <button class="btn btn-info">{{ $mode === 'create' ? 'Create template' : 'Save changes' }}</button>
            <a class="btn btn-outline-light" href="{{ route('superadmin.email-templates.index') }}">Back</a>
            @if ($mode === 'edit')
                <a class="btn btn-outline-light" href="{{ route('superadmin.email-templates.preview', $template) }}">Preview</a>
            @endif
        </div>
    </form>
</div>

@if ($placeholderHelp !== [])
<div class="sa-card">
    <h2 class="h5 text-white mb-3">Placeholder variables</h2>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Variable</th><th>Description</th></tr></thead>
            <tbody>
            @foreach ($placeholderHelp as $key => $description)
                <tr>
                    <td><code>{{'{{'.$key.'}}'}}</code></td>
                    <td class="sa-muted">{{ $description }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if ($mode === 'edit')
<div class="sa-card">
    <h2 class="h5 text-white mb-3">Send test email</h2>
    <form method="POST" action="{{ route('superadmin.email-templates.test', $template) }}" class="form-row align-items-end">
        @csrf
        <div class="form-group col-md-6 mb-md-0">
            <label class="sa-muted">Recipient</label>
            <input type="email" name="to_email" class="form-control" value="{{ old('to_email', auth()->user()->email) }}" required>
        </div>
        <div class="form-group col-md-3 mb-0">
            <button class="btn btn-outline-light">Send test</button>
        </div>
    </form>
</div>

@if (($recentLogs ?? collect())->isNotEmpty())
<div class="sa-card">
    <h2 class="h5 text-white mb-3">Recent send / preview log</h2>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>When</th><th>To</th><th>Status</th><th>Subject</th></tr></thead>
            <tbody>
            @foreach ($recentLogs as $log)
                <tr>
                    <td class="sa-muted">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->to_email }}</td>
                    <td>{{ $log->status }}</td>
                    <td class="sa-muted">{{ \Illuminate\Support\Str::limit($log->subject, 40) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<form method="POST" action="{{ route('superadmin.email-templates.destroy', $template) }}" class="sa-card"
      onsubmit="return confirm('Delete this email template?')">
    @csrf
    @method('DELETE')
    <button class="btn btn-outline-danger">Delete template</button>
</form>
@endif
@endsection
