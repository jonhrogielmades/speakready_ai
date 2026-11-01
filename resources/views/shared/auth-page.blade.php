@php
   $authMode = ($authMode ?? 'login') === 'register' ? 'register' : 'login';
   $isRegister = $authMode === 'register';
   $layoutQuery = ($isMobile ?? false) ? ['sr_layout' => 'mobile', 'sr_is_mobile' => '1'] : [];
   $loginPageUrl = route('login', $layoutQuery);
   $registerPageUrl = route('register', $layoutQuery);
   $googleAuthUrl = $isRegister
      ? route('auth.google.register', $layoutQuery)
      : route('auth.google.login', $layoutQuery);
   $authQueryAlert = request('auth_alert') === 'google_login_unregistered'
      ? 'This Google account is not registered. Please register first.'
      : null;
   $pageError = $errors->first() ?: $authQueryAlert;
   $strongPasswordsRequired = \App\Support\SystemSettings::enabled('sec_strong_pass', true);
   $registerInitialStep = ($errors->has('password') || $errors->has('password_confirmation'))
      ? 3
      : (($errors->has('username') || $errors->has('email')) ? 2 : 1);
   $brandName = $systemName ?? 'SpeakReady AI';
   $brandLogo = $systemLogo ?? 'img/logo.png';
@endphp

<a class="auth-page-skip" href="#authFormPanel">Skip to {{ $isRegister ? 'registration' : 'login' }} form</a>

<main id="authPage" class="auth-page-shell" data-auth-mode="{{ $authMode }}">
   <header class="auth-page-topbar">
      <a class="auth-page-brand" href="{{ url('/') }}" aria-label="{{ $brandName }} home">
         <span class="auth-page-brand-mark"><img src="{{ asset($brandLogo) }}" alt=""></span>
         <span>{{ $brandName }}</span>
      </a>
      <div class="auth-page-topbar-actions">
         <button type="button" class="auth-page-theme-toggle" id="authPageThemeToggle" aria-label="Switch color theme" title="Switch color theme">
            <i class="fa-regular fa-sun" data-auth-theme-icon="sun" aria-hidden="true"></i>
            <i class="fa-regular fa-moon" data-auth-theme-icon="moon" aria-hidden="true"></i>
         </button>
         <a class="auth-page-back" href="{{ url('/') }}" aria-label="Exit to home" title="Exit to home">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Back to home</span>
         </a>
      </div>
   </header>

   <section class="auth-page-card" aria-labelledby="authPageTitle">
      <aside class="auth-page-promo" aria-label="Why use SpeakReady AI">
         <div class="auth-page-orb auth-page-orb-one" aria-hidden="true"></div>
         <div class="auth-page-orb auth-page-orb-two" aria-hidden="true"></div>
         <div class="auth-page-promo-content">
            <span class="auth-page-promo-kicker"><i class="fa-solid fa-sparkles" aria-hidden="true"></i> Practice with purpose</span>
            <h2>{{ $isRegister ? 'Build confidence before interview day.' : 'Welcome back. Keep moving forward.' }}</h2>
            <p>{{ $isRegister ? 'Create your workspace and turn each practice session into measurable progress.' : 'Continue your practice, refine your delivery, and prepare with confidence.' }}</p>
            <ul class="auth-page-benefits">
               <li><span><i class="fa-solid fa-bullseye" aria-hidden="true"></i></span><div><strong>Practice with purpose</strong><small>Role-focused questions and realistic interview scenarios.</small></div></li>
               <li><span><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></span><div><strong>Get useful feedback</strong><small>See what worked and what to improve in your next answer.</small></div></li>
               <li><span><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span><div><strong>Track real progress</strong><small>Follow your readiness as your practice adds up.</small></div></li>
            </ul>
         </div>
         <div class="auth-page-promo-footer">
            <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Secure access</span>
            <span><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i> Works on any device</span>
         </div>
      </aside>

      <div class="auth-page-form-side" id="authFormPanel" tabindex="-1">
         <div class="auth-page-form-wrap">
            <div class="auth-page-heading">
               <span class="auth-page-heading-icon" aria-hidden="true"><i class="fa-solid {{ $isRegister ? 'fa-user-plus' : 'fa-lock' }}"></i></span>
               <div>
                  <p class="auth-page-eyebrow">{{ $isRegister ? 'Start for free' : 'Secure account access' }}</p>
                  <h1 id="authPageTitle">{{ $isRegister ? 'Create your account' : 'Log in to your account' }}</h1>
               </div>
            </div>
            <p class="auth-page-intro">{{ $isRegister ? 'Set up your free practice workspace in a few steps.' : 'Use your username and password to continue.' }}</p>

            @if(session('success'))
               <div class="auth-page-alert auth-page-alert-success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
            @endif

            @if(!$isRegister && $errors->has('account_inactive'))
               <div class="auth-page-alert auth-page-alert-warning" role="alert">
                  <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                  <div>
                     <strong>Account access is paused</strong>
                     <p>{{ $errors->first('account_inactive') }}</p>
                     <form action="{{ route('request.reactivation') }}" method="POST">
                        @csrf
                        <input type="hidden" name="username" value="{{ old('username', old('email')) }}">
                        <button type="submit" class="auth-page-reactivation">Request reactivation</button>
                     </form>
                  </div>
               </div>
            @elseif($pageError)
               <div class="auth-page-alert auth-page-alert-error" role="alert" tabindex="-1" data-auth-error-summary>
                  <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                  <div><strong>{{ $isRegister ? 'Please review your details' : 'We could not log you in' }}</strong><p>{{ $pageError }}</p></div>
               </div>
            @endif

            <a href="{{ $googleAuthUrl }}" class="auth-page-google" data-auth-transition="{{ $isRegister ? 'google-register' : 'google-login' }}">
               @include('shared.google-auth-mark')
               <span>{{ $isRegister ? 'Sign up with Google' : 'Continue with Google' }}</span>
               <i class="fa-solid fa-arrow-right auth-page-google-arrow" aria-hidden="true"></i>
            </a>

            <div class="auth-page-divider"><span>{{ $isRegister ? 'or create an account with your details' : 'or log in with your username' }}</span></div>

            @if($isRegister)
               <form id="signupForm" class="auth-page-form" action="{{ route('register.submit') }}" method="POST" autocomplete="on" data-auth-submit="register" @if($isMobile) data-register-steps data-register-step="{{ $registerInitialStep }}" @endif>
                  @csrf
                  <input type="hidden" name="auth_tab" value="signup">
                  @if($isMobile)
                     <nav class="auth-register-stepper auth-mobile-register-only" aria-label="Registration progress" data-register-stepper>
                        <ol>
                           <li data-register-step-indicator="1" class="is-active">
                              <span class="auth-register-step-marker"><span data-register-step-number>1</span><i class="fa-solid fa-check" data-register-step-check aria-hidden="true" hidden></i></span>
                              <span>Full name</span>
                           </li>
                           <li data-register-step-indicator="2">
                              <span class="auth-register-step-marker"><span data-register-step-number>2</span><i class="fa-solid fa-check" data-register-step-check aria-hidden="true" hidden></i></span>
                              <span>Account</span>
                           </li>
                           <li data-register-step-indicator="3">
                              <span class="auth-register-step-marker"><span data-register-step-number>3</span><i class="fa-solid fa-check" data-register-step-check aria-hidden="true" hidden></i></span>
                              <span>Password</span>
                           </li>
                        </ol>
                        <p data-register-step-status aria-live="polite">Step {{ $registerInitialStep }} of 3</p>
                     </nav>
                  @endif
                  <div class="auth-page-fields{{ $isMobile ? ' auth-mobile-register-fields' : '' }}">
                     <div class="auth-mobile-register-step" data-register-step-panel="1">
                     <div class="auth-page-field">
                        <label for="signupName">Full name</label>
                        <div class="auth-page-input-wrap">
                           <i class="fa-regular fa-user" aria-hidden="true"></i>
                           <input class="auth-page-input" type="text" name="name" id="signupName" placeholder="Your full name" required autocomplete="name" value="{{ old('name') }}" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @if($errors->has('name')) aria-describedby="signupNameError" @endif>
                        </div>
                        @error('name')<small class="auth-page-field-error" id="signupNameError"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</small>@enderror
                     </div>
                        @if($isMobile)
                           <button type="button" class="auth-page-step-next" data-register-next="1"><span>Continue to account</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                        @endif
                     </div>

                     <div class="auth-mobile-register-step" data-register-step-panel="2">
                     <div class="auth-page-field">
                        <label for="signupUsername">Username</label>
                        <div class="auth-page-input-wrap">
                           <i class="fa-solid fa-at" aria-hidden="true"></i>
                           <input class="auth-page-input" type="text" name="username" id="signupUsername" placeholder="your_username" required minlength="3" maxlength="64" pattern="[a-z0-9._-]+" autocapitalize="none" autocomplete="username" spellcheck="false" value="{{ old('username') }}" aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}" aria-describedby="signupUsernameHelp{{ $errors->has('username') ? ' signupUsernameError' : '' }}">
                        </div>
                        <small class="auth-page-field-help" id="signupUsernameHelp">Use lowercase letters, numbers, dots, dashes, or underscores.</small>
                        @error('username')<small class="auth-page-field-error" id="signupUsernameError"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</small>@enderror
                     </div>

                     <div class="auth-page-field auth-page-field-full">
                        <label for="signupEmail">Recovery email</label>
                        <div class="auth-page-input-wrap">
                           <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                           <input class="auth-page-input" type="email" name="email" id="signupEmail" placeholder="you@example.com" required maxlength="255" inputmode="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" title="Enter a valid recovery email address." autocomplete="email" spellcheck="false" value="{{ old('email') }}" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="signupEmailHelp{{ $errors->has('email') ? ' signupEmailError' : '' }}">
                        </div>
                        <small class="auth-page-field-help" id="signupEmailHelp">Used only for password recovery. We will send a link to verify it.</small>
                        @error('email')<small class="auth-page-field-error" id="signupEmailError"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</small>@enderror
                     </div>
                        @if($isMobile)
                           <button type="button" class="auth-page-step-next" data-register-next="2"><span>Continue to password</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                        @endif
                     </div>

                     <div class="auth-mobile-register-step" data-register-step-panel="3">
                     <div class="auth-page-field">
                        <label for="signupPass">Password</label>
                        <div class="auth-page-input-wrap auth-page-password-wrap">
                           <i class="fa-solid fa-lock" aria-hidden="true"></i>
                           <input class="auth-page-input" type="password" name="password" id="signupPass" placeholder="Create a password" required minlength="8" autocomplete="new-password" data-strong-password="{{ $strongPasswordsRequired ? 'true' : 'false' }}" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="signupPasswordHelp signupPasswordStatus{{ $errors->has('password') ? ' signupPasswordError' : '' }}">
                           <button type="button" class="auth-page-password-toggle" data-password-toggle aria-controls="signupPass" aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <small class="auth-page-field-help" id="signupPasswordHelp">{{ $strongPasswordsRequired ? 'Use 8+ characters with uppercase, lowercase, a number, and a symbol.' : 'Use at least 8 characters.' }}</small>
                        <ul class="auth-page-password-rules" id="signupPasswordStatus" aria-label="Password requirements">
                           <li data-password-rule="length"><i class="fa-solid fa-circle" aria-hidden="true"></i>8+ characters</li>
                           @if($strongPasswordsRequired)
                              <li data-password-rule="lower"><i class="fa-solid fa-circle" aria-hidden="true"></i>Lowercase</li>
                              <li data-password-rule="upper"><i class="fa-solid fa-circle" aria-hidden="true"></i>Uppercase</li>
                              <li data-password-rule="number"><i class="fa-solid fa-circle" aria-hidden="true"></i>Number</li>
                              <li data-password-rule="symbol"><i class="fa-solid fa-circle" aria-hidden="true"></i>Symbol</li>
                           @endif
                        </ul>
                        <span id="signupPasswordValid" class="auth-page-password-status is-valid" role="status" hidden><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Password is valid.</span>
                        <span id="signupPasswordInvalid" class="auth-page-password-status is-invalid" role="status" hidden><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>Password still needs attention.</span>
                        @error('password')<small class="auth-page-field-error" id="signupPasswordError"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</small>@enderror
                     </div>

                     <div class="auth-page-field">
                        <label for="signupPassConfirm">Confirm password</label>
                        <div class="auth-page-input-wrap auth-page-password-wrap">
                           <i class="fa-solid fa-lock" aria-hidden="true"></i>
                           <input class="auth-page-input" type="password" name="password_confirmation" id="signupPassConfirm" placeholder="Enter it again" required minlength="8" autocomplete="new-password" aria-describedby="signupConfirmHelp">
                           <button type="button" class="auth-page-password-toggle" data-password-toggle aria-controls="signupPassConfirm" aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <small class="auth-page-field-help" id="signupConfirmHelp" data-confirm-password-status>Re-enter your password to confirm it.</small>
                     </div>
                     </div>
                  </div>

                  <div class="auth-mobile-register-final">
                     <div class="auth-page-check auth-page-terms">
                        <input type="checkbox" name="terms_accepted" value="1" id="signupTerms" required @checked(old('terms_accepted'))>
                        <label for="signupTerms">I understand that I will review and accept the <a href="{{ route('legal.terms') }}">Terms of Service</a> and <a href="{{ route('legal.privacy') }}">Privacy Policy</a> before entering my dashboard.</label>
                     </div>
                     @error('terms_accepted')<small class="auth-page-field-error auth-page-terms-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</small>@enderror
                     <button type="submit" class="auth-page-submit" id="signupBtn"><span>Create free account</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                  </div>
               </form>
            @else
               <form id="loginForm" class="auth-page-form" action="{{ route('login.submit') }}" method="POST" autocomplete="on" data-auth-submit="login">
                  @csrf
                  <input type="hidden" name="auth_tab" value="login">
                  <div class="auth-page-field">
                     <label for="loginUsername">Username</label>
                     <div class="auth-page-input-wrap">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <input class="auth-page-input" type="text" name="username" id="loginUsername" placeholder="Enter your username" required autocomplete="username" autocapitalize="none" spellcheck="false" value="{{ old('username') }}" aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}" @if($errors->has('username')) aria-describedby="loginUsernameError" @endif autofocus>
                     </div>
                     @error('username')<small class="auth-page-field-error" id="loginUsernameError"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</small>@enderror
                  </div>

                  <div class="auth-page-field">
                     <div class="auth-page-label-row">
                        <label for="loginPass">Password</label>
                     </div>
                     <div class="auth-page-input-wrap auth-page-password-wrap">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input class="auth-page-input" type="password" name="password" id="loginPass" placeholder="Enter your password" required autocomplete="current-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if($errors->has('password')) aria-describedby="loginPasswordError" @endif>
                        <button type="button" class="auth-page-password-toggle" data-password-toggle aria-controls="loginPass" aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                     </div>
                     @error('password')<small class="auth-page-field-error" id="loginPasswordError"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ $message }}</small>@enderror
                     <div class="auth-page-forgot-link"><a href="{{ route('password.request') }}">Forgot password?</a></div>
                  </div>

                  <div class="auth-page-options">
                     <div class="auth-page-check">
                        <input type="hidden" name="remember" value="0">
                        <input type="checkbox" name="remember" value="1" id="loginRemember" @checked(old('remember'))>
                        <label for="loginRemember">Keep me signed in on this device</label>
                     </div>
                  </div>
                  <button type="submit" class="auth-page-submit" id="loginBtn"><span>Log in securely</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
               </form>
            @endif

            <div class="auth-page-switch">
               <span>{{ $isRegister ? 'Already have an account?' : 'New to '.$brandName.'?' }}</span>
               <a href="{{ $isRegister ? $loginPageUrl : $registerPageUrl }}">{{ $isRegister ? 'Log in' : 'Create an account' }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>

            <p class="auth-page-security-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Your information is encrypted and handled securely.</p>
         </div>
      </div>
   </section>

   <footer class="auth-page-footer">
      <span>&copy; {{ date('Y') }} {{ $brandName }}</span><span aria-hidden="true">&bull;</span>
      <a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('legal.terms') }}">Terms</a>
   </footer>
</main>

<div id="authTransitionOverlay" class="auth-page-transition" role="status" aria-live="polite" aria-busy="true" hidden>
   <div class="auth-page-transition-card">
      <span class="auth-page-transition-logo" aria-hidden="true"><span></span><img src="{{ asset($brandLogo) }}" alt=""></span>
      <h2 id="authTransitionTitle">{{ $isRegister ? 'Creating your account...' : 'Logging you in...' }}</h2>
      <p id="authTransitionCopy">Please wait a moment.</p>
   </div>
</div>
