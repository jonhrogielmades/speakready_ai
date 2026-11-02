@php
   $supportMode = ($supportMode ?? 'forgot') === 'reset' ? 'reset' : 'forgot';
   $isReset = $supportMode === 'reset';
   $layoutQuery = ($isMobile ?? false) ? ['sr_layout' => 'mobile', 'sr_is_mobile' => '1'] : [];
   $brandName = $systemName ?? 'SpeakReady AI';
   $brandLogo = $systemLogo ?? 'img/logo.png';
   $strongPasswordsRequired = \App\Support\SystemSettings::enabled('sec_strong_pass', true);
@endphp

<a class="auth-page-skip" href="#authFormPanel">Skip to password {{ $isReset ? 'update' : 'recovery' }} form</a>

<main id="authPage" class="auth-page-shell auth-support-page" data-auth-mode="{{ $supportMode }}">
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
            <span class="auth-page-promo-kicker"><i class="fa-solid fa-shield-check" aria-hidden="true"></i> Secure account support</span>
            <h2>{{ $isReset ? 'Set a password you can trust.' : 'Get back to your practice.' }}</h2>
            <p>{{ $isReset ? 'Choose a strong password and return to interview practice with confidence.' : 'Recover your local account securely, then keep building the skills that make you memorable.' }}</p>
            <ul class="auth-page-benefits">
               <li class="auth-page-benefit auth-page-benefit-private"><span class="auth-page-benefit-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span><div><strong>Private by design</strong><small>Your recovery details are used only to restore account access.</small></div></li>
               <li class="auth-page-benefit auth-page-benefit-recovery"><span class="auth-page-benefit-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></span><div><strong>One-time recovery</strong><small>Recovery codes expire and can only be used once.</small></div></li>
               <li class="auth-page-benefit auth-page-benefit-progress"><span class="auth-page-benefit-icon"><i class="fa-solid fa-arrow-rotate-right" aria-hidden="true"></i></span><div><strong>Back to progress</strong><small>Return to your practice workspace when you are ready.</small></div></li>
            </ul>
         </div>
         <div class="auth-page-promo-footer">
            <span><i class="fa-solid fa-shield-check" aria-hidden="true"></i> Secure access</span>
            <span><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i> Works on any device</span>
         </div>
      </aside>

      <div class="auth-page-form-side" id="authFormPanel" tabindex="-1">
         <div class="auth-page-form-wrap">
            <div class="auth-page-heading">
               <span class="auth-page-heading-icon" aria-hidden="true"><i class="fa-solid {{ $isReset ? 'fa-lock' : 'fa-key' }}"></i></span>
               <div>
                  <p class="auth-page-eyebrow">{{ $isReset ? 'Secure password update' : 'Account recovery' }}</p>
                  <h1 id="authPageTitle">{{ $isReset ? 'Create a new password' : 'Reset your password' }}</h1>
               </div>
            </div>
            <p class="auth-page-intro">{{ $isReset ? 'Choose a new password for your SpeakReady AI account.' : 'Use your username and recovery email to receive a one-time code.' }}</p>

            @if (session('status'))
               <div class="auth-page-alert auth-page-alert-success" role="status">
                  <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ session('status') }}</span>
               </div>
            @endif

            @if ($errors->any())
               <div class="auth-page-alert auth-page-alert-error" role="alert" tabindex="-1" data-auth-error-summary>
                  <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                  <div><strong>Please review your details</strong><p>{{ $errors->first() }}</p></div>
               </div>
            @endif

            @if ($isReset)
               <form action="{{ route('password.update') }}" method="POST" class="auth-page-form auth-page-support-form" data-auth-submit="reset">
                  @csrf
                  <input type="hidden" name="token" value="{{ $token }}">

                  <div class="auth-page-field">
                     <label for="resetEmail">Email address</label>
                     <div class="auth-page-input-wrap">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <input class="auth-page-input" id="resetEmail" type="email" name="email" value="{{ old('email', $email) }}" placeholder="you@example.com" autocomplete="email" required>
                     </div>
                  </div>

                  <div class="auth-page-field">
                     <label for="resetPassword">New password</label>
                     <div class="auth-page-input-wrap auth-page-password-wrap">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input class="auth-page-input" id="resetPassword" type="password" name="password" placeholder="{{ $strongPasswordsRequired ? '8+ chars: upper, lower, number & symbol' : 'At least 8 characters' }}" autocomplete="new-password" minlength="8" data-password-strength data-strong-password="{{ $strongPasswordsRequired ? 'true' : 'false' }}" aria-describedby="resetPasswordHelp resetPasswordStatus" required>
                        <button type="button" class="auth-page-password-toggle" data-password-toggle aria-controls="resetPassword" aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                     </div>
                     <small id="resetPasswordHelp" class="auth-page-field-help">{{ $strongPasswordsRequired ? 'Use 8+ characters with uppercase, lowercase, a number, and a symbol.' : 'Use at least 8 characters.' }}</small>
                     <ul class="auth-page-password-rules" id="resetPasswordStatus" aria-label="Password requirements">
                        <li data-password-rule="length"><i class="fa-solid fa-circle" aria-hidden="true"></i>8+ characters</li>
                        @if($strongPasswordsRequired)
                           <li data-password-rule="lower"><i class="fa-solid fa-circle" aria-hidden="true"></i>Lowercase</li>
                           <li data-password-rule="upper"><i class="fa-solid fa-circle" aria-hidden="true"></i>Uppercase</li>
                           <li data-password-rule="number"><i class="fa-solid fa-circle" aria-hidden="true"></i>Number</li>
                           <li data-password-rule="symbol"><i class="fa-solid fa-circle" aria-hidden="true"></i>Symbol</li>
                        @endif
                     </ul>
                     <span class="auth-page-password-status is-valid" data-password-valid role="status" hidden><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Password is valid.</span>
                     <span class="auth-page-password-status is-invalid" data-password-invalid role="status" hidden><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>Password still needs attention.</span>
                  </div>

                  <div class="auth-page-field">
                     <label for="resetPasswordConfirm">Confirm password</label>
                     <div class="auth-page-input-wrap auth-page-password-wrap">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <input class="auth-page-input" id="resetPasswordConfirm" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" minlength="8" data-password-confirm aria-describedby="resetConfirmHelp" required>
                        <button type="button" class="auth-page-password-toggle" data-password-toggle aria-controls="resetPasswordConfirm" aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                     </div>
                     <small id="resetConfirmHelp" class="auth-page-field-help" data-confirm-password-status>Re-enter your password to confirm it.</small>
                  </div>

                  <button type="submit" class="auth-page-submit"><span>Update password</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
               </form>
               <div class="auth-page-switch">
                  <span>Return to login?</span>
                  <a href="{{ route('login', $layoutQuery) }}">Log in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
               </div>
            @else
               <form action="{{ route('password.recovery.send') }}" method="POST" class="auth-page-form auth-page-support-form" data-auth-submit="recovery">
                  @csrf
                  <div class="auth-page-field">
                     <label for="recoveryUsername">Username</label>
                     <div class="auth-page-input-wrap">
                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                        <input class="auth-page-input" id="recoveryUsername" type="text" name="username" value="{{ old('username') }}" placeholder="your_username" autocomplete="username" autocapitalize="none" spellcheck="false" required>
                     </div>
                  </div>

                  <div class="auth-page-field">
                     <label for="recoveryEmail">Recovery email</label>
                     <div class="auth-page-input-wrap">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <input class="auth-page-input" id="recoveryEmail" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required>
                     </div>
                     <small class="auth-page-field-help">This must match the verified email saved on your local account.</small>
                  </div>

                  <button type="submit" class="auth-page-submit"><span>Send Recovery Code</span><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
               </form>
               <div class="auth-page-switch">
                  <span>Return to login?</span>
                  <a href="{{ route('login', $layoutQuery) }}">Log in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
               </div>

               @if (session('recovery_code_sent'))
                  <div class="auth-page-divider auth-page-support-divider"><span>Enter the one-time code</span></div>
                  <p class="auth-page-support-copy">Check your inbox, then enter the code below. It expires in 15 minutes and works once.</p>
                  <form action="{{ route('password.recovery.reset') }}" method="POST" class="auth-page-form auth-page-support-form" data-auth-submit="reset">
                     @csrf
                     <input type="hidden" name="username" value="{{ old('username') }}">

                     <div class="auth-page-field">
                        <label for="recoveryCode">Recovery code</label>
                        <div class="auth-page-input-wrap">
                           <i class="fa-solid fa-key" aria-hidden="true"></i>
                           <input class="auth-page-input" id="recoveryCode" type="text" name="recovery_code" placeholder="ABCD-EFGH-IJKL-MNOP" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" required>
                        </div>
                     </div>

                     <div class="auth-page-field">
                        <label for="recoveryPassword">New password</label>
                        <div class="auth-page-input-wrap auth-page-password-wrap">
                           <i class="fa-solid fa-lock" aria-hidden="true"></i>
                           <input class="auth-page-input" id="recoveryPassword" type="password" name="password" placeholder="{{ $strongPasswordsRequired ? '8+ chars: upper, lower, number & symbol' : 'At least 8 characters' }}" autocomplete="new-password" minlength="8" required>
                           <button type="button" class="auth-page-password-toggle" data-password-toggle aria-controls="recoveryPassword" aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <small class="auth-page-field-help">{{ $strongPasswordsRequired ? 'Use 8+ characters with uppercase, lowercase, a number, and a symbol.' : 'Use at least 8 characters.' }}</small>
                     </div>

                     <div class="auth-page-field">
                        <label for="recoveryPasswordConfirm">Confirm password</label>
                        <div class="auth-page-input-wrap auth-page-password-wrap">
                           <i class="fa-solid fa-lock" aria-hidden="true"></i>
                           <input class="auth-page-input" id="recoveryPasswordConfirm" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" minlength="8" required>
                           <button type="button" class="auth-page-password-toggle" data-password-toggle aria-controls="recoveryPasswordConfirm" aria-label="Show password"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                        </div>
                     </div>

                     <button type="submit" class="auth-page-submit"><span>Reset password</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                  </form>
               @endif
            @endif

            <p class="auth-page-security-note"><i class="fa-solid fa-shield-check" aria-hidden="true"></i><span>Your information is encrypted and handled securely.</span></p>
         </div>
      </div>
   </section>

   <footer class="auth-page-footer">
      <span class="auth-page-footer-brand"><span>&copy; {{ date('Y') }} {{ $brandName }}</span></span>
      <span class="auth-page-footer-divider" aria-hidden="true"></span>
      <a href="{{ route('legal.privacy') }}"><i class="fa-solid fa-shield-check" aria-hidden="true"></i><span>Privacy</span></a>
      <a href="{{ route('legal.terms') }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Terms</span></a>
   </footer>
</main>

<div id="authTransitionOverlay" class="auth-page-transition" role="status" aria-live="polite" aria-busy="true" hidden>
   <div class="auth-page-transition-card">
      <span class="auth-page-transition-logo" aria-hidden="true"><span></span><img src="{{ asset($brandLogo) }}" alt=""></span>
      <h2 id="authTransitionTitle">{{ $isReset ? 'Updating your password...' : 'Sending recovery details...' }}</h2>
      <p id="authTransitionCopy">Please wait a moment.</p>
   </div>
</div>
