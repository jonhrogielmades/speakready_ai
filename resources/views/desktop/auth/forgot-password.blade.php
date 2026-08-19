@extends('desktop.layouts.auth')

@section('title', 'Forgot Password - SpeakReady AI')

@section('content')
   <h1 class="auth-title">Reset your password</h1>
   <p class="auth-copy">Enter your account email and we will send you a secure password reset link.</p>

   @if (session('status'))
      <div class="auth-alert auth-alert-success mb-3">
         <i class="fa-solid fa-check-circle me-1"></i> {{ session('status') }}
      </div>
   @endif

   @if ($errors->any())
      <div class="auth-alert auth-alert-error mb-3">
         <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $errors->first() }}
      </div>
   @endif

   <form action="{{ route('password.email') }}" method="POST">
      @csrf
      <div class="mb-3">
         <label class="auth-label" for="email"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
         <input class="form-control auth-input" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
      </div>

      <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">
         Send Reset Link <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i>
      </button>
   </form>

   <div class="text-center mt-4">
      <a class="auth-muted-link" href="{{ url('/') }}">Back to login</a>
   </div>
@endsection
