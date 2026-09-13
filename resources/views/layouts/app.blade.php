@extends('adminlte::page')

@section('title', config('app.name'))

@section('content_header')
    @isset($header)
        {!! $header !!}
    @endisset
@stop

@section('content')
    @include('partials.impersonation-banner')
    {{ $slot }}
    @include('partials.crm-shell')
@stop

@section('css')
    {{-- tokens + app shell load via AdminLTE CrmUi plugin (also covers auth pages) --}}
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @stack('css')
@stop

@section('js')
    @auth
        @if(auth()->user()->hasAnyPermission(['view.leads', 'view.customers', 'view.tasks', 'view.users']))
            @include('partials.global-search-autocomplete')
        @endif
    @endauth
    @stack('js')
@stop
