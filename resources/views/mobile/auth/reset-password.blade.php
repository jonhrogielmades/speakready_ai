@extends('mobile.layouts.auth')

@section('title', 'Reset Password - SpeakReady AI')
@section('body_class', 'auth-page-reset')

@section('content')
   <div class="auth-kicker"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Secure reset</div>
   <h1 class="auth-title">Create a new password</h1>
   <p class="auth-copy">Choose a new password for your SpeakReady AI account.</p>

   @if ($errors->any())
      <div class="auth-alert auth-alert-error mb-3">
         <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $errors->first() }}
      </div>
   @endif

   <form action="{{ route('password.update') }}" method="POST">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <div class="mb-3">
         <label class="auth-label" for="email"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
         <input class="form-control auth-input" id="email" type="email" name="email" value="{{ old('email', $email) }}" placeholder="you@example.com" required autofocus>
      </div>

      <div class="mb-3">
         <label class="auth-label" for="password"><i class="fa-solid fa-lock me-1"></i>New password</label>
         <div class="auth-password-wrap">
            <input class="form-control auth-input" id="password" type="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password" required>
            <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show password"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
         </div>
      </div>

      <div class="mb-4">
         <label class="auth-label" for="password_confirmation"><i class="fa-solid fa-lock me-1"></i>Confirm password</label>
         <div class="auth-password-wrap">
            <input class="form-control auth-input" id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" required>
            <button type="button" class="auth-password-toggle" data-password-toggle="password_confirmation" aria-label="Show password"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
         </div>
      </div>

      <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">
         Update Password <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i>
      </button>
   </form>
@endsection
