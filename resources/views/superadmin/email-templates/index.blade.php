@extends('superadmin.layout')

@section('title', 'Email Templates')
@section('heading', 'Email Templates')
@section('subheading', 'Manage transactional email content, placeholders, and branding')

@section('content')
<div class="sa-card">
    <form method="GET" class="form-row align-items-end">
        <div class="form-group col-md-3 mb-md-0">
            <label class="sa-muted">Search</label>
            <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or subject">
        </div>
        <div class="form-group col-md-2 mb-md-0">
            <label class="sa-muted">Category</label>
            <select class="custom-select" name="category">
                <option value="">All</option>
                @foreach ($categories as $key => $meta)
                    <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-2 mb-md-0">
            <label class="sa-muted">Locale</label>
            <select class="custom-select" name="locale">
                <option value="">All</option>
                @foreach ($locales as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['locale'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-2 mb-md-0">
            <label class="sa-muted">Status</label>
            <select class="custom-select" name="status">
                <option value="">All</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="form-group col-md-3 mb-0">
            <button class="btn btn-outline-light mr-2">Filter</button>
            <a class="btn btn-info" href="{{ route('superadmin.email-templates.create') }}">New template</a>
        </div>
    </form>
</div>

<div class="sa-card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Template</th>
                <th>Category</th>
                <th>Locale</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Version</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($templates as $template)
                <tr>
                    <td>
                        <strong class="text-white">{{ $template->name }}</strong>
                        <div class="sa-muted small">{{ $template->slug }}</div>
                    </td>
                    <td>{{ $template->categoryLabel() }}</td>
                    <td>{{ strtoupper($template->locale) }}</td>
                    <td class="sa-muted">{{ \Illuminate\Support\Str::limit($template->subject, 48) }}</td>
                    <td>
                        <span class="badge badge-{{ $template->is_active ? 'active' : 'suspended' }}">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>v{{ $template->version }}</td>
                    <td>
                        <a class="btn btn-sm btn-outline-light" href="{{ route('superadmin.email-templates.edit', $template) }}">Edit</a>
                        <a class="btn btn-sm btn-outline-light" href="{{ route('superadmin.email-templates.preview', $template) }}">Preview</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="sa-empty">
                            <h2 class="sa-empty__title">No email templates found</h2>
                            <p class="sa-empty__text">Seed defaults or create a template for a category and locale.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $templates->links() }}</div>
</div>
@endsection
