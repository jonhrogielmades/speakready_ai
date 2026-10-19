@extends('desktop.layouts.auth')

@section('title', 'Log In - SpeakReady AI')
@section('body_class', 'auth-page-login')

@section('content')
   <div class="auth-kicker"><i class="fa-solid fa-sparkles" aria-hidden="true"></i> Welcome back</div>
   <h1 class="auth-title">Log in to SpeakReady AI</h1>
   <p class="auth-copy">Pick up your interview practice right where you left off.</p>
   @include('shared.auth.login-form')
@endsection
