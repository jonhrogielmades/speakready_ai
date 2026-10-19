@php
   $googleAuthLayoutQuery = ($isMobile ?? false) ? ['sr_layout' => 'mobile', 'sr_is_mobile' => '1'] : [];
   $googleAuthAlert = request('auth_alert') === 'google_login_unregistered'
      ? 'This Google account is not registered. Please register first.'
      : null;
@endphp

@if (session('success'))
   <div class="auth-alert auth-alert-success mb-3" role="status">
      <i class="fa-solid fa-check-circle me-1" aria-hidden="true"></i> {{ session('success') }}
   </div>
@endif

@if ($errors->has('account_inactive'))
   <div class="auth-alert auth-alert-error mb-3" role="alert">
      <div class="mb-2"><i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i>{{ $errors->first('account_inactive') }}</div>
      <form action="{{ route('request.reactivation') }}" method="POST">
         @csrf
         <input type="hidden" name="username" value="{{ old('username', old('email')) }}">
         <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold auth-reactivation-button">Request Reactivation</button>
      </form>
   </div>
@elseif ($errors->any() || $googleAuthAlert)
   <div class="auth-alert auth-alert-error mb-3" role="alert">
      <i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i> {{ $errors->first() ?: $googleAuthAlert }}
   </div>
@endif

<form action="{{ route('login.submit') }}" method="POST" class="auth-form" autocomplete="on">
   @csrf
   <div class="mb-3">
      <label class="auth-label" for="loginUsername"><i class="fa-solid fa-user me-1" aria-hidden="true"></i>Username</label>
      <input class="form-control auth-input" id="loginUsername" type="text" name="username" value="{{ old('username') }}" placeholder="your_username" autocomplete="username" required autofocus>
   </div>

   <div class="mb-2">
      <label class="auth-label" for="loginPassword"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Password</label>
      <div class="auth-password-wrap">
         <input class="form-control auth-input" id="loginPassword" type="password" name="password" placeholder="Your password" autocomplete="current-password" required>
         <button type="button" class="auth-password-toggle" data-password-toggle="loginPassword" aria-label="Show password"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></button>
      </div>
   </div>

   <div class="auth-options-row mb-4">
      <label class="auth-check" for="loginRemember">
         <input type="hidden" name="remember" value="0">
         <input type="checkbox" name="remember" value="1" id="loginRemember" checked>
         <span>Keep me signed in</span>
      </label>
      <a href="{{ route('password.request') }}" class="auth-muted-link">Forgot password?</a>
   </div>

   <button type="submit" class="bgrd btn w-100 py-3 fw-semibold auth-submit">Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm" aria-hidden="true"></i></button>
</form>

<div class="auth-divider" aria-hidden="true"><span>or continue with</span></div>

<a href="{{ route('auth.google.login', $googleAuthLayoutQuery) }}" class="auth-social-button" data-auth-transition="google">
   @include('shared.google-auth-mark')
   <span>Log in with Google</span>
</a>

<p class="auth-switch">Don't have an account? <a href="{{ route('register') }}">Register for free</a></p>
