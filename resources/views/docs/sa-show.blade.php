@extends('superadmin.layout')

@section('title', 'Documentation')

@section('content')
    <div class="mb-3">
        <h1 class="h3 mb-1">Documentation</h1>
        <p class="text-muted mb-0">{{ $title }}</p>
    </div>

    @include('docs._content')
@endsection
