@extends('mobile.layouts.admin')
@section('title', 'Admin Account Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/account.css?v=2') }}" data-page-style="admin-account">
<link rel="stylesheet" href="{{ asset('css/mobile/user/account-2.css?v=3') }}" data-page-style="admin-account-2">
<link rel="stylesheet" href="{{ asset('css/mobile/admin/account.css?v=2') }}" data-page-style="admin-account-page">
@endpush

@section('content')
@include('mobile.partials.page-hero-styles')
@include('shared.admin.account-content')
@endsection
