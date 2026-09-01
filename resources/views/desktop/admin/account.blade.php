@extends('desktop.layouts.admin')
@section('title', 'Admin Account Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/account.css?v=2') }}" data-page-style="admin-account">
<link rel="stylesheet" href="{{ asset('css/desktop/user/account-2.css?v=5') }}" data-page-style="admin-account-2">
<link rel="stylesheet" href="{{ asset('css/desktop/admin/account.css?v=2') }}" data-page-style="admin-account-page">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')
@include('shared.admin.account-content')
@endsection
