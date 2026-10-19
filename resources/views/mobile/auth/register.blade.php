@extends('mobile.layouts.auth')

@section('title', 'Register - SpeakReady AI')
@section('body_class', 'auth-page-register')

@section('content')
   <div class="auth-kicker"><i class="fa-solid fa-rocket" aria-hidden="true"></i> Start practicing</div>
   <h1 class="auth-title">Create your free account</h1>
   <p class="auth-copy">Build confident answers with focused practice and feedback.</p>
   @include('shared.auth.register-form')
@endsection
