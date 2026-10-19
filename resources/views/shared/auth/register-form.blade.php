@php
   $googleAuthLayoutQuery = ($isMobile ?? false) ? ['sr_layout' => 'mobile', 'sr_is_mobile' => '1'] : [];
@endphp

@if ($errors->any())
   <div class="auth-alert auth-alert-error mb-3" role="alert">
      <i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i> {{ $errors->first() }}
   </div>
@endif

<form action="{{ route('register.submit') }}" method="POST" class="auth-form" autocomplete="on">
   @csrf
   <div class="mb-3">
      <label class="auth-label" for="registerName"><i class="fa-solid fa-user me-1" aria-hidden="true"></i>Full name</label>
      <input class="form-control auth-input" id="registerName" type="text" name="name" value="{{ old('name') }}" placeholder="John Doe" autocomplete="name" required autofocus>
   </div>

   <div class="mb-3">
      <label class="auth-label" for="registerUsername"><i class="fa-solid fa-user-tag me-1" aria-hidden="true"></i>Username</label>
      <input class="form-control auth-input" id="registerUsername" type="text" name="username" value="{{ old('username') }}" placeholder="your_username" autocomplete="username" required>
   </div>

   <div class="mb-3">
      <label class="auth-label" for="registerEmail"><i class="fa-regular fa-envelope me-1" aria-hidden="true"></i>Recovery email</label>
      <input class="form-control auth-input" id="registerEmail" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" inputmode="email" spellcheck="false" required>
      <small class="auth-help">Used only for password recovery codes.</small>
   </div>

   <div class="mb-3">
      <label class="auth-label" for="registerPassword"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Password</label>
      <div class="auth-password-wrap">
         <input class="form-control auth-input" id="registerPassword" type="password" name="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
         <button type="button" class="auth-password-toggle" data-password-toggle="registerPassword" aria-label="Show password"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
      </div>
   </div>

   <div class="mb-4">
      <label class="auth-label" for="registerPasswordConfirmation"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Confirm password</label>
      <div class="auth-password-wrap">
         <input class="form-control auth-input" id="registerPasswordConfirmation" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" minlength="8" required>
         <button type="button" class="auth-password-toggle" data-password-toggle="registerPasswordConfirmation" aria-label="Show password"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
      </div>
   </div>

   <button type="submit" class="bgrd btn w-100 py-3 fw-semibold auth-submit">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm" aria-hidden="true"></i></button>
</form>

<div class="auth-divider" aria-hidden="true"><span>or sign up with</span></div>

<a href="{{ route('auth.google.register', $googleAuthLayoutQuery) }}" class="auth-social-button" data-auth-transition="google-register">
   @include('shared.google-auth-mark')
   <span>Sign up with Google</span>
</a>

<p class="auth-terms">By creating an account, you agree to our <a href="{{ route('legal.terms') }}">Terms</a> and <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.</p>
<p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
