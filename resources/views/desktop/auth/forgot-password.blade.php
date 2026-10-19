@extends('desktop.layouts.auth')

@section('title', 'Forgot Password - SpeakReady AI')
@section('body_class', 'auth-page-recovery')

@section('content')
   <div class="auth-kicker"><i class="fa-solid fa-key" aria-hidden="true"></i> Account recovery</div>
   <h1 class="auth-title">Reset your password</h1>
   <p class="auth-copy">Use your username and the recovery email saved on your local account. We will send a one-time recovery code to that email.</p>

   @if (session('status'))
      <div class="auth-alert auth-alert-success mb-3" role="status">
         <i class="fa-solid fa-check-circle me-1"></i> {{ session('status') }}
      </div>
   @endif

   @if ($errors->any())
      <div class="auth-alert auth-alert-error mb-3" role="alert">
         <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $errors->first() }}
      </div>
   @endif

   <form action="{{ route('password.recovery.send') }}" method="POST" class="mb-4">
      @csrf
      <div class="mb-3">
         <label class="auth-label" for="username"><i class="fa-solid fa-user me-1"></i>Username</label>
         <input class="form-control auth-input" id="username" type="text" name="username" value="{{ old('username') }}" placeholder="your_username" autocomplete="username" required autofocus>
      </div>

      <div class="mb-3">
         <label class="auth-label" for="email"><i class="fa-regular fa-envelope me-1"></i>Recovery Gmail or email</label>
         <input class="form-control auth-input" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@gmail.com" autocomplete="email" required>
         <small class="d-block mt-2 text-muted">This must match the email saved on your local account.</small>
      </div>

      <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">
         Send Recovery Code <i class="fa-solid fa-paper-plane ms-1 fa-sm"></i>
      </button>
   </form>

   @if (session('recovery_code_sent'))
      <div class="auth-copy mb-3" style="font-size:.9rem">Check your inbox, then enter the code below. It expires in 15 minutes and works once.</div>
      <form action="{{ route('password.recovery.reset') }}" method="POST">
         @csrf
         <div class="mb-3">
            <label class="auth-label" for="recovery_code"><i class="fa-solid fa-key me-1"></i>Recovery code</label>
            <input class="form-control auth-input" id="recovery_code" type="text" name="recovery_code" value="" placeholder="ABCD-EFGH-IJKL-MNOP" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" required>
         </div>

         <div class="mb-3">
            <label class="auth-label" for="password"><i class="fa-solid fa-lock me-1"></i>New password</label>
            <div class="auth-password-wrap">
               <input class="form-control auth-input" id="password" type="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password" required>
               <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show password"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
            </div>
         </div>

         <div class="mb-4">
            <label class="auth-label" for="password_confirmation"><i class="fa-solid fa-lock me-1"></i>Confirm new password</label>
            <div class="auth-password-wrap">
               <input class="form-control auth-input" id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" required>
               <button type="button" class="auth-password-toggle" data-password-toggle="password_confirmation" aria-label="Show password"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
            </div>
         </div>

         <input type="hidden" name="username" value="{{ old('username') }}">
         <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">
            Reset Password <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i>
         </button>
      </form>
   @endif

   <p class="auth-copy mt-3" style="font-size:.82rem">Google Login accounts should continue using Google Login. If you cannot access your recovery email, contact the administrator.</p>

   <div class="text-center mt-4">
      <a class="auth-muted-link" href="{{ route('login') }}">Back to login</a>
   </div>
@endsection
