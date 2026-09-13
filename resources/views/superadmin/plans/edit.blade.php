@extends('superadmin.layout')
@section('title', 'Edit '.$plan->name)
@section('heading', 'Edit Subscription Plan')
@section('subheading', 'Manage business data, features, and limits without changing presentation')
@section('content')
<div class="sa-card" style="max-width: 760px;">
    <form method="POST" action="{{ route('superadmin.plans.update', $plan) }}">
        @csrf @method('PUT')
        @include('superadmin.plans.form')
        <button class="btn btn-info">Save changes</button>
        <a class="btn btn-outline-light ml-2" href="{{ route('superadmin.plans.index') }}">Back</a>
    </form>
</div>
<div class="sa-card mt-3"><div class="d-flex justify-content-between align-items-center"><div><strong class="text-white">Duplicate this plan</strong><div class="sa-muted small">Copies plan details, features, and limits as a private inactive draft.</div></div><form method="POST" action="{{ route('superadmin.plans.duplicate', $plan) }}">@csrf<button class="btn btn-outline-light">Duplicate</button></form></div></div>
@endsection
