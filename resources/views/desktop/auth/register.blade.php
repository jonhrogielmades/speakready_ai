@extends('desktop.layouts.auth')

@section('title', 'Register - SpeakReady AI')
@section('body_class', 'auth-page-register')

@section('content')
   <h1 class="auth-title">Create Your Account</h1>
   <p class="auth-copy">Start practicing with your SpeakReady AI account</p>
   @include('shared.auth.register-form')
@endsection
