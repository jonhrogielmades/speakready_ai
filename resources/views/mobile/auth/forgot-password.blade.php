@extends('mobile.layouts.auth')

@section('title', 'Forgot Password - SpeakReady AI')

@section('content')
   <h1 class="auth-title">Reset your password</h1>
   <p class="auth-copy">Enter your username and one-time recovery code to create a new password. No email is required.</p>

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

   <form action="{{ route('password.recovery.reset') }}" method="POST">
      @csrf
      <div class="mb-3">
         <label class="auth-label" for="username"><i class="fa-solid fa-user me-1"></i>Username</label>
         <input class="form-control auth-input" id="username" type="text" name="username" value="{{ old('username') }}" placeholder="your_username" autocomplete="username" required autofocus>
      </div>

      <div class="mb-3">
         <label class="auth-label" for="recovery_code"><i class="fa-solid fa-key me-1"></i>Recovery code</label>
         <input class="form-control auth-input" id="recovery_code" type="text" name="recovery_code" value="" placeholder="ABCD-EFGH-IJKL-MNOP" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" required>
         <small class="d-block mt-2 text-muted">Use the recovery code saved when your account was created.</small>
      </div>

      <div class="mb-3">
         <label class="auth-label" for="password"><i class="fa-solid fa-lock me-1"></i>New password</label>
         <input class="form-control auth-input" id="password" type="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password" required>
      </div>

      <div class="mb-4">
         <label class="auth-label" for="password_confirmation"><i class="fa-solid fa-lock me-1"></i>Confirm new password</label>
         <input class="form-control auth-input" id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" required>
      </div>

      <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">
         Reset Password <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i>
      </button>
   </form>

   <p class="auth-copy mt-3" style="font-size:.82rem">Lost your recovery code? Contact the administrator for assisted account recovery.</p>

   <div class="text-center mt-4">
      <a class="auth-muted-link" href="{{ url('/') }}">Back to login</a>
   </div>
@endsection
