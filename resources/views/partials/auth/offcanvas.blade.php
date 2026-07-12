      <!-- ======================== LOGIN OFFCANVAS ======================== -->
      <div class="offcanvas offcanvas-end" tabindex="-1" id="lofc">
         <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:30px;height:30px;background: transparent; padding: 0;">
               <h5 class="offcanvas-title mb-0">SpeakReady AI</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" style="filter:invert(1)"></button>
         </div>
         <div class="offcanvas-body p-4">
            <div class="tab-switch"><button class="tab-sw-btn on" id="tabLogin" onclick="swTab('login')">Log In</button><button class="tab-sw-btn" id="tabSignup" onclick="swTab('signup')">Register</button></div>
            <!-- Login -->
            <div id="fLogin">
               @if(session('success'))
                  <div style="background:rgba(52,211,153,0.1);color:#34d399;border:1px solid rgba(52,211,153,0.2);padding:10px;border-radius:12px;font-size:0.85rem;margin-bottom:15px;">
                     <i class="fa-solid fa-check-circle me-1"></i> {{ session('success') }}
                  </div>
               @endif
               @if($errors->has('account_inactive'))
                  <div class="err-msg" style="display:block; padding:12px; margin-bottom:15px; text-align:left; line-height: 1.4;">
                     <div class="mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first('account_inactive') }}</span></div>
                     <form action="{{ route('request.reactivation') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email') }}">
                        <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold" style="border-radius:8px; background: #f59e0b; border: none; color: #fff;">Request Reactivation</button>
                     </form>
                  </div>
               @endif
               <form id="loginForm" action="{{ route('login') }}" method="POST">
      <!-- ======================== LOGIN OFFCANVAS ======================== -->
      <div class="offcanvas offcanvas-end" tabindex="-1" id="lofc">
         <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:30px;height:30px;background: transparent; padding: 0;">
               <h5 class="offcanvas-title mb-0">SpeakReady AI</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" style="filter:invert(1)"></button>
         </div>
         <div class="offcanvas-body p-4">
            <div class="tab-switch"><button class="tab-sw-btn on" id="tabLogin" onclick="swTab('login')">Log In</button><button class="tab-sw-btn" id="tabSignup" onclick="swTab('signup')">Register</button></div>
            <!-- Login -->
            <div id="fLogin">
               @if(session('success'))
                  <div style="background:rgba(52,211,153,0.1);color:#34d399;border:1px solid rgba(52,211,153,0.2);padding:10px;border-radius:12px;font-size:0.85rem;margin-bottom:15px;">
                     <i class="fa-solid fa-check-circle me-1"></i> {{ session('success') }}
                  </div>
               @endif
               @if($errors->has('account_inactive'))
                  <div class="err-msg" style="display:block; padding:12px; margin-bottom:15px; text-align:left; line-height: 1.4;">
                     <div class="mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first('account_inactive') }}</span></div>
                     <form action="{{ route('request.reactivation') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email') }}">
                        <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold" style="border-radius:8px; background: #f59e0b; border: none; color: #fff;">Request Reactivation</button>
                     </form>
                  </div>
               @endif
               <form id="loginForm" action="{{ route('login') }}" method="POST">
                  @csrf
                  @if($errors->any() && !$errors->has('account_inactive') && !old('name'))
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl"><i class="fa-solid fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="loginEmail" placeholder="you@example.com" required value="{{ old('email') }}">
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password" id="loginPass" placeholder="********" required>
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('loginPass', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <div class="text-end mb-3" style="margin-top:-8px"><a href="#" style="font-size:.8rem;color:var(--pur)">Forgot password?</a></div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="loginBtn">Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or continue with</div>
               <a href="{{ route('auth.google') }}" class="oauth" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Continue with Google</a>

               <p class="text-center mt-4" style="font-size:.82rem;color:var(--tx3)">Don't have an account? <a href="#" style="color:var(--pur)" onclick="swTab('signup');return false">Register for free</a></p>
            </div>
            <!-- Sign Up -->
            <div id="fSignup" style="display:none">
               <form action="{{ route('register') }}" method="POST">
                  @csrf
                  @if($errors->any() && old('name'))
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl"><i class="fa-solid fa-user me-1"></i>Full name</label>
                  <input class="oinp" type="text" name="name" id="signupName" placeholder="John Doe" required value="{{ old('name') }}">
                  <label class="olbl"><i class="fa-solid fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="signupEmail" placeholder="you@example.com" required>
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password" id="signupPass" placeholder="Min. 8 characters" required>
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPass', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Confirm Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password_confirmation" id="signupPassConfirm" placeholder="Confirm your password" required>
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPassConfirm', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="signupBtn">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or sign up with</div>
               <a href="{{ route('auth.google') }}" class="oauth" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Continue with Google</a>

               <p class="text-center mt-3" style="font-size:.76rem;color:var(--tx3)">By signing up, you agree to our <a href="#" style="color:var(--pur)">Terms</a> &amp; <a href="#" style="color:var(--pur)">Privacy Policy</a></p>
            </div>
            <!-- Close Button at Bottom -->
            <div class="text-center mt-5 mb-3">
               <button type="button" class="boc d-inline-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas" style="width: 48px; height: 48px; border-radius: 50%; opacity: 0.8;" aria-label="Close">
                  <i class="fa-solid fa-xmark fs-5"></i>
               </button>
            </div>
         </div>
      </div>

      <script>
         function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (!input || !icon) return;
            if (input.type === 'password') {
               input.type = 'text';
               icon.classList.remove('fa-eye-slash');
               icon.classList.add('fa-eye');
               btn.setAttribute('aria-label', 'Hide password');
            } else {
               input.type = 'password';
               icon.classList.remove('fa-eye');
               icon.classList.add('fa-eye-slash');
               btn.setAttribute('aria-label', 'Show password');
            }
         }
      </script>

