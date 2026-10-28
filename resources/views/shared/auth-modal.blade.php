@php
   $googleAuthLayoutQuery = ($isMobile ?? false) ? ['sr_layout' => 'mobile', 'sr_is_mobile' => '1'] : [];
   $authModalRequest = request('auth_modal');
   $authTab = old('auth_tab') ?: (in_array($authModalRequest, ['login', 'signup', 'register'], true) ? $authModalRequest : null);
   $showSignupAuthTab = in_array($authTab, ['signup', 'register'], true) || ($authTab === null && filled(old('name')));
   $authQueryAlert = request('auth_alert') === 'google_login_unregistered'
      ? 'This Google account is not registered. Please register first.'
      : null;
   $loginAuthAlert = ! $showSignupAuthTab
      ? ($errors->first() ?: $authQueryAlert)
      : null;
@endphp

      <!-- ======================== LOGIN MODAL ======================== -->
      <div class="modal auth-modal" tabindex="-1" id="lofc" aria-labelledby="authModalTitle" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered auth-modal-dialog">
            <div class="modal-content auth-modal-content">
               <div class="auth-modal-header">
                  <div class="auth-modal-brand">
                     <span class="auth-logo-frame">
                        <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
                     </span>
                     <div class="auth-brand-copy">
                        <h5 class="modal-title auth-brand-title mb-0" id="authModalTitle">SpeakReady AI</h5>
                        <span class="auth-brand-subtitle">Practice Smarter. Interview Better.</span>
                     </div>
                  </div>
                  <button type="button" class="auth-close-button" data-bs-dismiss="modal" aria-label="Close">
                     <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                  </button>
               </div>
               <div class="modal-body auth-modal-body">
                  <div class="tab-switch auth-tab-switch" role="tablist" aria-label="Authentication options">
                     <button type="button" class="tab-sw-btn @unless($showSignupAuthTab) on @endunless" id="tabLogin" role="tab" aria-selected="{{ $showSignupAuthTab ? 'false' : 'true' }}" aria-controls="fLogin" onclick="swTab('login')">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <span>Log In</span>
                     </button>
                     <button type="button" class="tab-sw-btn @if($showSignupAuthTab) on @endif" id="tabSignup" role="tab" aria-selected="{{ $showSignupAuthTab ? 'true' : 'false' }}" aria-controls="fSignup" onclick="swTab('signup')">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                        <span>Register</span>
                     </button>
                  </div>

                  <!-- Login -->
                  <div id="fLogin" class="auth-panel" role="tabpanel" aria-labelledby="tabLogin" @if($showSignupAuthTab) style="display:none" hidden aria-hidden="true" @else aria-hidden="false" @endif>
                     @if(session('success'))
                        <div class="auth-success-msg" role="status">
                           <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
                           <span>{{ session('success') }}</span>
                        </div>
                     @endif
                     @if($errors->has('account_inactive'))
                        <div class="err-msg auth-inline-alert is-visible" role="alert">
                           <div class="mb-2"><i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i><span>{{ $errors->first('account_inactive') }}</span></div>
                           <form action="{{ route('request.reactivation') }}" method="POST">
                              @csrf
                              <input type="hidden" name="username" value="{{ old('username', old('email')) }}">
                              <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold auth-reactivation-button">Request Reactivation</button>
                           </form>
                        </div>
                     @endif
                     <form id="loginForm" class="auth-form" action="{{ route('login') }}" method="POST" autocomplete="on">
                        @csrf
                        <input type="hidden" name="auth_tab" value="login">
                        @if(($errors->any() || $loginAuthAlert) && !$errors->has('account_inactive') && ! $showSignupAuthTab)
                           <div class="err-msg is-visible" role="alert"><i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i><span>{{ $loginAuthAlert }}</span></div>
                        @endif
                        <div class="auth-field">
                           <label class="olbl" for="loginUsername"><i class="fa-solid fa-user" aria-hidden="true"></i>Username</label>
                           <div class="auth-input-wrap">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                              <input class="oinp" type="text" name="username" id="loginUsername" placeholder="your_username" required autocomplete="username" value="{{ old('username') }}">
                           </div>
                        </div>
                        <div class="auth-field">
                           <label class="olbl" for="loginPass"><i class="fa-solid fa-lock" aria-hidden="true"></i>Password</label>
                           <div class="auth-input-wrap password-field">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                              <input class="oinp" type="password" name="password" id="loginPass" placeholder="********" required autocomplete="current-password">
                              <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('loginPass', this)" aria-label="Show password">
                                 <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                              </button>
                           </div>
                        </div>
                        <div class="auth-options-row">
                           <div class="form-check auth-check">
                              <input type="hidden" name="remember" value="0">
                              <input class="form-check-input" type="checkbox" name="remember" value="1" id="loginRemember" checked>
                              <label class="form-check-label" for="loginRemember">Keep me signed in on this device</label>
                           </div>
                           <a href="{{ route('password.request') }}" class="auth-inline-link">Forgot password?</a>
                        </div>
                        <button type="submit" class="bgrd btn auth-submit w-100 fw-semibold" id="loginBtn">Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm" aria-hidden="true"></i></button>
                     </form>
                     <div class="odiv auth-divider">or continue with</div>
                     <a href="{{ route('auth.google.login', $googleAuthLayoutQuery) }}" class="oauth auth-oauth" data-auth-transition="google">
                        @include('shared.google-auth-mark')
                        <span>Log in with Google</span>
                     </a>
                     <p class="auth-secure-note"><i class="fa-regular fa-shield-check" aria-hidden="true"></i><span>Your data is secure with us.</span></p>
                  </div>

                  <!-- Sign Up -->
                  <div id="fSignup" class="auth-panel" role="tabpanel" aria-labelledby="tabSignup" @unless($showSignupAuthTab) style="display:none" hidden aria-hidden="true" @else aria-hidden="false" @endunless>
                     <form id="signupForm" class="auth-form" action="{{ route('register') }}" method="POST" autocomplete="on">
                        @csrf
                        <input type="hidden" name="auth_tab" value="signup">
                        @if($errors->any() && $showSignupAuthTab)
                           <div class="err-msg is-visible" role="alert"><i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
                        @endif
                        <div class="auth-field auth-register-name">
                           <label class="olbl" for="signupName"><i class="fa-solid fa-user" aria-hidden="true"></i>Full name</label>
                           <div class="auth-input-wrap">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                              <input class="oinp" type="text" name="name" id="signupName" placeholder="John Doe" required autocomplete="name" value="{{ old('name') }}">
                           </div>
                        </div>
                        <div class="auth-field auth-register-username">
                           <label class="olbl" for="signupUsername"><i class="fa-solid fa-user-tag" aria-hidden="true"></i>Username</label>
                           <div class="auth-input-wrap">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-user-tag"></i></span>
                              <input class="oinp" type="text" name="username" id="signupUsername" placeholder="your_username" required autocomplete="username" value="{{ old('username') }}">
                           </div>
                        </div>
                        <div class="auth-field auth-register-email">
                           <label class="olbl" for="signupEmail"><i class="fa-solid fa-envelope" aria-hidden="true"></i>Recovery email</label>
                           <div class="auth-input-wrap">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                              <input class="oinp" type="email" name="email" id="signupEmail" placeholder="you@example.com" required maxlength="255" inputmode="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" title="Enter a valid recovery email address." autocomplete="email" spellcheck="false" aria-describedby="signupEmailHelp" value="{{ old('email') }}">
                           </div>
                           <small id="signupEmailHelp" class="d-block mt-1" style="color:var(--tx3);font-size:.76rem">This address is used only for password recovery. We will email a verification link to confirm you own it.</small>
                        </div>
                        <div class="auth-field auth-register-password">
                           <label class="olbl" for="signupPass"><i class="fa-solid fa-lock" aria-hidden="true"></i>Password</label>
                           <div class="auth-input-wrap password-field">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                              <input class="oinp" type="password" name="password" id="signupPass" placeholder="Min. 8 characters" required autocomplete="new-password">
                              <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPass', this)" aria-label="Show password">
                                 <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                              </button>
                           </div>
                        </div>
                        <div class="auth-field auth-register-confirm-password">
                           <label class="olbl" for="signupPassConfirm"><i class="fa-solid fa-lock" aria-hidden="true"></i>Confirm password</label>
                           <div class="auth-input-wrap password-field">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                              <input class="oinp" type="password" name="password_confirmation" id="signupPassConfirm" placeholder="Confirm your password" required autocomplete="new-password">
                              <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPassConfirm', this)" aria-label="Show password">
                                 <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                              </button>
                           </div>
                        </div>
                        <div class="form-check auth-check auth-terms">
                           <input class="form-check-input" type="checkbox" name="terms_accepted" value="1" id="signupTerms" required @checked(old('terms_accepted'))>
                           <label class="form-check-label" for="signupTerms">I understand I will review and accept the <a href="{{ route('legal.terms') }}">Terms of Service</a> and <a href="{{ route('legal.privacy') }}">Privacy Policy</a> before entering my dashboard.</label>
                        </div>
                        <button type="submit" class="bgrd btn auth-submit w-100 fw-semibold" id="signupBtn">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm" aria-hidden="true"></i></button>
                     </form>
                     <div class="odiv auth-divider">or sign up with</div>
                     <a href="{{ route('auth.google.register', $googleAuthLayoutQuery) }}" class="oauth auth-oauth" data-auth-transition="google-register">
                        @include('shared.google-auth-mark')
                        <span>Sign up with Google</span>
                     </a>
                     <p class="auth-secure-note"><i class="fa-regular fa-shield-check" aria-hidden="true"></i><span>Your information is safe and secure.</span></p>
                  </div>
               </div>
               <div class="auth-wave" aria-hidden="true"><span></span></div>
            </div>
         </div>
      </div>
