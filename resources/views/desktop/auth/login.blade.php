@extends('desktop.layouts.auth')

@section('title', 'Log In - SpeakReady AI')
@section('body_class', 'auth-page-login')

@section('content')
   <h1 class="auth-title">Welcome Back</h1>
   <p class="auth-copy">Sign in to your SpeakReady AI account</p>
   @include('shared.auth.login-form')
@endsection
