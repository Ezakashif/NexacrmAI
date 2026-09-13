@extends('superadmin.layout')

@section('title', 'Preview · '.$template->name)
@section('heading', 'Preview')
@section('subheading', $template->categoryLabel().' · '.strtoupper($template->locale))

@section('content')
<div class="sa-card d-flex flex-wrap align-items-center justify-content-between" style="gap:0.75rem;">
    <div class="sa-muted">Rendered with sample placeholder data and platform branding settings.</div>
    <div>
        <a class="btn btn-outline-light" href="{{ route('superadmin.email-templates.edit', $template) }}">Edit</a>
        <a class="btn btn-outline-light" href="{{ route('superadmin.email-templates.index') }}">All templates</a>
    </div>
</div>

<div class="sa-card" style="padding:0;overflow:hidden;">
    <iframe title="Email preview" style="width:100%;min-height:720px;border:0;background:#fff;" srcdoc="{{ e($previewHtml) }}"></iframe>
</div>
@endsection
