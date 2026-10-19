@extends('mobile.layouts.auth')

@section('title', 'Reset Password - SpeakReady AI')

@section('content')
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
         <input class="form-control auth-input" id="password" type="password" name="password" placeholder="Min. 8 characters" required>
      </div>

      <div class="mb-4">
         <label class="auth-label" for="password_confirmation"><i class="fa-solid fa-lock me-1"></i>Confirm password</label>
         <input class="form-control auth-input" id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm your password" required>
      </div>

      <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">
         Update Password <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i>
      </button>
   </form>
@endsection
