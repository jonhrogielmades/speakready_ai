      <!-- ======================== LOGIN MODAL ======================== -->
      <div class="modal fade auth-modal" tabindex="-1" id="lofc" aria-labelledby="authModalTitle" aria-hidden="true">
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
                     <button type="button" class="tab-sw-btn on" id="tabLogin" role="tab" aria-selected="true" aria-controls="fLogin" onclick="swTab('login')">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <span>Log In</span>
                     </button>
                     <button type="button" class="tab-sw-btn" id="tabSignup" role="tab" aria-selected="false" aria-controls="fSignup" onclick="swTab('signup')">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                        <span>Register</span>
                     </button>
                  </div>

                  <!-- Login -->
                  <div id="fLogin" class="auth-panel" role="tabpanel" aria-labelledby="tabLogin">
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
                              <input type="hidden" name="email" value="{{ old('email') }}">
                              <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold auth-reactivation-button">Request Reactivation</button>
                           </form>
                        </div>
                     @endif
                     <form id="loginForm" class="auth-form" action="{{ route('login') }}" method="POST" autocomplete="off">
                        @csrf
                        @if($errors->any() && !$errors->has('account_inactive') && !old('name'))
                           <div class="err-msg is-visible" role="alert"><i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
                        @endif
                        <div class="auth-field">
                           <label class="olbl" for="loginEmail"><i class="fa-solid fa-envelope" aria-hidden="true"></i>Email address</label>
                           <div class="auth-input-wrap">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                              <input class="oinp" type="email" name="email" id="loginEmail" placeholder="you@email.com" required autocomplete="off" value="{{ old('email') }}">
                           </div>
                        </div>
                        <div class="auth-field">
                           <label class="olbl" for="loginPass"><i class="fa-solid fa-lock" aria-hidden="true"></i>Password</label>
                           <div class="auth-input-wrap password-field">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                              <input class="oinp" type="password" name="password" id="loginPass" placeholder="********" required autocomplete="new-password">
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
                     <a href="{{ route('auth.google.login') }}" class="oauth auth-oauth" data-auth-transition="google">
                        <i class="fa-brands fa-google" style="color:#EA4335;" aria-hidden="true"></i>
                        <span>Log in with Google</span>
                     </a>
                     <p class="auth-secure-note"><i class="fa-regular fa-shield-check" aria-hidden="true"></i><span>Your data is secure with us.</span></p>
                  </div>

                  <!-- Sign Up -->
                  <div id="fSignup" class="auth-panel" role="tabpanel" aria-labelledby="tabSignup" style="display:none">
                     <form id="signupForm" class="auth-form" action="{{ route('register') }}" method="POST" autocomplete="off">
                        @csrf
                        @if($errors->any() && old('name'))
                           <div class="err-msg is-visible" role="alert"><i class="fa-solid fa-circle-exclamation me-1" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
                        @endif
                        <div class="auth-field">
                           <label class="olbl" for="signupName"><i class="fa-solid fa-user" aria-hidden="true"></i>Full name</label>
                           <div class="auth-input-wrap">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                              <input class="oinp" type="text" name="name" id="signupName" placeholder="John Doe" required autocomplete="off" value="{{ old('name') }}">
                           </div>
                        </div>
                        <div class="auth-field">
                           <label class="olbl" for="signupEmail"><i class="fa-solid fa-envelope" aria-hidden="true"></i>Email address</label>
                           <div class="auth-input-wrap">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                              <input class="oinp" type="email" name="email" id="signupEmail" placeholder="you@email.com" required autocomplete="off" value="{{ old('email') }}">
                           </div>
                        </div>
                        <div class="auth-field">
                           <label class="olbl" for="signupPass"><i class="fa-solid fa-lock" aria-hidden="true"></i>Password</label>
                           <div class="auth-input-wrap password-field">
                              <span class="auth-input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                              <input class="oinp" type="password" name="password" id="signupPass" placeholder="Min. 8 characters" required autocomplete="new-password">
                              <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPass', this)" aria-label="Show password">
                                 <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                              </button>
                           </div>
                        </div>
                        <div class="auth-field">
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
                           <input class="form-check-input" type="checkbox" name="terms_accepted" value="1" id="signupTerms" checked required>
                           <label class="form-check-label" for="signupTerms">I agree to the <a href="{{ route('legal.terms') }}">Terms of Service</a> and <a href="{{ route('legal.privacy') }}">Privacy Policy</a></label>
                        </div>
                        <button type="submit" class="bgrd btn auth-submit w-100 fw-semibold" id="signupBtn">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm" aria-hidden="true"></i></button>
                     </form>
                     <div class="odiv auth-divider">or sign up with</div>
                     <a href="{{ route('auth.google.register') }}" class="oauth auth-oauth" data-auth-transition="google-register">
                        <i class="fa-brands fa-google" style="color:#EA4335;" aria-hidden="true"></i>
                        <span>Sign up with Google</span>
                     </a>
                     <p class="auth-secure-note"><i class="fa-regular fa-shield-check" aria-hidden="true"></i><span>Your information is safe and secure.</span></p>
                  </div>
               </div>
               <div class="auth-wave" aria-hidden="true"><span></span></div>
            </div>
         </div>
      </div>
