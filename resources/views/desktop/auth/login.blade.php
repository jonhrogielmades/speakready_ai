@extends('desktop.layouts.auth')

@section('title', 'Log In - SpeakReady AI')
@section('auth-shell-class', 'auth-page-shell')

@section('auth-page-content')
   @include('shared.auth-modal', ['authPageMode' => true, 'authPage' => 'login'])
@endsection

@push('styles')
   <link rel="stylesheet" href="{{ asset('css/shared/auth-page.css?v=1') }}">
@endpush

@push('scripts')
   @include('shared.google-auth-mobile-layout-guard')
   <script src="{{ asset('js/auth-page.js?v=1') }}"></script>
@endpush
