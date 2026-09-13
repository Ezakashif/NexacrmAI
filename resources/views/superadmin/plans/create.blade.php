@extends('superadmin.layout')
@section('title', 'New Subscription Plan')
@section('heading', 'New Subscription Plan')
@section('subheading', 'Set the business rules for a customer-facing plan')
@section('content')
<div class="sa-card" style="max-width: 760px;">
    <form method="POST" action="{{ route('superadmin.plans.store') }}">
        @csrf
        @include('superadmin.plans.form')
        <button class="btn btn-info">Create plan</button>
        <a class="btn btn-outline-light ml-2" href="{{ route('superadmin.plans.index') }}">Cancel</a>
    </form>
</div>
@endsection
