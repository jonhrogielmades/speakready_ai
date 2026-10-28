<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Mail\PasswordRecoveryCodeMail;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\UserOnboardingProgress;
use App\Services\AccountDeletionService;
use App\Services\BrevoTransactionalMail;
use App\Services\RecoveryEmailVerification;
use App\Models\User;
use App\Support\AccountNotificationSchema;
use App\Support\PasswordRecovery;
use App\Support\SystemSettings;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    private const LOGIN_PASSWORD_MISMATCH_MESSAGE = 'The password do not match our records.';

    private const LOGIN_CREDENTIALS_MISMATCH_MESSAGE = 'The provided credentials do not match our records.';

    private const LOGIN_UNREGISTERED_ACCOUNT_MESSAGE = 'This account is not registered. Please register first.';

    private const GOOGLE_LOGIN_UNREGISTERED_ACCOUNT_MESSAGE = 'This Google account is not registered. Please register first.';

    private const GOOGLE_LOGIN_UNREGISTERED_ALERT_KEY = 'google_login_unregistered';

    private const GOOGLE_AUTH_INTENT_SESSION_KEY = 'google_auth_intent';

    private const GOOGLE_AUTH_LAYOUT_SESSION_KEY = 'google_auth_layout';

    private const GOOGLE_AUTH_NONCE_SESSION_KEY = 'google_auth_nonce';

    private const GOOGLE_ACCOUNT_DELETE_USER_SESSION_KEY = 'google_account_delete_user_id';

    private const GOOGLE_ACCOUNT_DELETE_INTENT = 'account_delete';

    private const GOOGLE_AUTH_INTENTS = [
        'login',
        'register',
        self::GOOGLE_ACCOUNT_DELETE_INTENT,
    ];

    private const AUTH_TAB_OLD_INPUT_KEY = 'auth_tab';

    private const MOBILE_VIEWPORT_MAX = 820;

    public function register(Request $request, RecoveryEmailVerification $emailVerification)
    {
        if (! Setting::enabled('acc_registration')) {
            return back()->withErrors([
                'username' => 'New account registration is currently disabled by the administrator.',
            ])->withInput($request->only('name', 'username', self::AUTH_TAB_OLD_INPUT_KEY));
        }

        $request->merge([
            'username' => $this->normalizeUsername($request->input('username')),
            'email' => $this->normalizeEmail($request->input('email')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'email' => [
                'required',
                'string',
                'max:255',
                'email:rfc,filter',
                Rule::unique('users', 'email')->where(
                    fn ($query) => $query->where('auth_method', User::AUTH_METHOD_LOCAL)
                ),
            ],
            'password' => $this->newPasswordRules(['confirmed']),
        ], [
            'email.email' => 'Enter a valid recovery email address.',
            'email.unique' => 'This recovery email is already registered.',
        ]);

        $recoveryCode = PasswordRecovery::generate();

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'password_recovery_code_hash' => PasswordRecovery::hash($recoveryCode),
            'password_recovery_code_generated_at' => now(),
            'password_recovery_code_expires_at' => null,
            'email_verified_at' => null,
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        UserOnboardingProgress::forUser($user)->forceFill([
            'recovery_code_ack_required_at' => now(),
            'recovery_code_saved_at' => null,
        ])->save();

        $this->ensureAuthenticationProfile($user);

        Auth::login($user, true);
        $request->session()->regenerate();

        try {
            $emailVerification->send($user);
        } catch (Throwable $error) {
            Log::warning('Initial recovery email verification could not be delivered.', [
                'user_id' => $user->getKey(),
                'exception_type' => $error::class,
            ]);
        }

        $this->safeActivityLog(
            $user,
            'user_registered',
            "{$user->name} ({$this->userIdentifier($user)}) registered a new local account.",
            $request->ip(),
            false
        );

        $registrationMessage = 'Registration successful. Welcome to SpeakReady AI!';
        $route = $user->is_admin ? 'admin.dashboard' : 'terms.acceptance.show';

        return redirect()->route($route, sr_layout_query($request))
            ->with('success', $registrationMessage)
            ->with('registration_success', true)
            ->with('recovery_code', $recoveryCode);
    }

    public function login(Request $request, RecoveryEmailVerification $emailVerification)
    {
        $loginValue = $request->input('username', $request->input('email'));

        $request->merge([
            'username' => $this->normalizeUsername($loginValue),
        ]);

        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $rememberDevice = $request->boolean('remember', true);
        $identifier = $validated['username'];

        if ($lockoutMessage = $this->loginLockoutMessage($request, $identifier)) {
            return $this->failedLoginResponse('username', $lockoutMessage);
        }

        $user = $this->findUserByLoginIdentifier($identifier);

        if (! $user) {
            $this->recordFailedLoginAttempt($request, $identifier);

            return $this->failedLoginResponse('username', $this->loginFailureMessage(self::LOGIN_UNREGISTERED_ACCOUNT_MESSAGE));
        }

        if (! Hash::check($validated['password'], (string) $user->password)) {
            $this->recordFailedLoginAttempt($request, $identifier);

            return $this->failedLoginResponse(
                $this->genericLoginErrorsEnabled() ? 'username' : 'password',
                $this->loginFailureMessage(self::LOGIN_PASSWORD_MISMATCH_MESSAGE)
            );
        }

        Auth::login($user, $rememberDevice);

        if (Auth::check()) {
            $request->session()->regenerate();
            $user = Auth::user();
            $this->clearFailedLoginAttempts($request, $identifier);

            if (in_array($user->status, ['inactive', 'suspended'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'account_inactive' => 'Your account was inactivated please contact to the admin for request.',
                ])->withInput([
                    'username' => $identifier,
                ]);
            }

            if (! $user->is_admin && filled($user->email) && SystemSettings::enabled('acc_verify_email', false) && ! $user->hasVerifiedEmail()) {
                try {
                    $emailVerification->send($user);
                } catch (Throwable $error) {
                    Log::warning('Required recovery email verification could not be delivered during login.', [
                        'user_id' => $user->getKey(),
                        'exception_type' => $error::class,
                    ]);
                }

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'Recovery email verification is required before you can sign in. Check your inbox for a new verification link.',
                ])->withInput([
                    'username' => $identifier,
                ]);
            }

            $this->logAuthenticationActivity($user, 'user_logged_in', 'logged in', $request->ip());

            if ($user->is_admin) {
                return redirect()->route('admin.dashboard', sr_layout_query($request));
            }

            return redirect()->route('dashboard', sr_layout_query($request));
        }

        return $this->failedLoginResponse('username', self::LOGIN_CREDENTIALS_MISMATCH_MESSAGE);
    }

    public function requestReactivation(Request $request)
    {
        $loginValue = $request->input('username', $request->input('email'));

        $request->merge([
            'username' => $this->normalizeUsername($loginValue),
        ]);

        $validated = $request->validate([
            'username' => 'required|string|max:255',
        ]);

        $user = $this->findUserForReactivation($validated['username']);

        if ($user && in_array($user->status, ['inactive', 'suspended'])) {
            $user->update(['reactivation_requested_at' => now()]);

            $this->safeActivityLog(
                $user,
                'reactivation_requested',
                "{$user->name} ({$this->userIdentifier($user)}) requested account reactivation.",
                $request->ip(),
                false
            );

            return back()->with('success', 'Your reactivation request has been sent to the admin.');
        }

        return back()->withErrors([
            'username' => 'Unable to process your request at this time.',
        ])->onlyInput('username');
    }

    public function showForgotPasswordForm()
    {
        return $this->mobileView('auth.forgot-password');
    }

    public function sendRecoveryCode(Request $request)
    {
        $request->merge([
            'username' => $this->normalizeUsername($request->input('username')),
            'email' => $this->normalizeEmail($request->input('email')),
        ]);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $rateLimitKey = 'password-recovery-request:'.sha1($validated['username'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Too many recovery requests. Please try again in 15 minutes.'])
                ->withInput($request->only('username', 'email'));
        }

        RateLimiter::hit($rateLimitKey, 900);

        $user = User::query()
            ->where('auth_method', User::AUTH_METHOD_LOCAL)
            ->whereRaw('LOWER(username) = ?', [$validated['username']])
            ->whereRaw('LOWER(email) = ?', [$validated['email']])
            ->first();

        if (! $user || blank($user->email)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'The username and recovery email do not match a local account.'])
                ->withInput($request->only('username', 'email'));
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Verify this recovery email from your account before requesting an emailed recovery code.'])
                ->withInput($request->only('username', 'email'));
        }

        $recoveryCode = PasswordRecovery::generate();
        $expiresInMinutes = max(5, (int) config('auth.passwords.users.recovery_code_expire', 15));
        $recoveryCodeHash = PasswordRecovery::hash($recoveryCode);
        $recoveryCodeGeneratedAt = now();
        $recoveryCodeExpiresAt = $recoveryCodeGeneratedAt->copy()->addMinutes($expiresInMinutes);

        try {
            $this->assertPasswordRecoverySchemaReady();

            DB::transaction(function () use ($user, $recoveryCodeHash, $recoveryCodeGeneratedAt, $recoveryCodeExpiresAt): void {
                $lockedUser = User::query()
                    ->whereKey($user->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedUser->forceFill([
                    'password_recovery_code_hash' => $recoveryCodeHash,
                    'password_recovery_code_generated_at' => $recoveryCodeGeneratedAt,
                    'password_recovery_code_expires_at' => $recoveryCodeExpiresAt,
                ])->saveOrFail();
            });
        } catch (Throwable $e) {
            Log::error('Password recovery code could not be stored.', [
                'failure' => 'storage',
                'username_hash' => hash('sha256', $validated['username']),
                'email_hash' => hash('sha256', $validated['email']),
                'exception_type' => $e::class,
                'exception_message' => Str::limit($e->getMessage(), 500),
            ]);

            return redirect()->route('password.request')
                ->withErrors(['email' => 'Password recovery is temporarily unavailable because the recovery database is not ready. Please contact the administrator.'])
                ->withInput($request->only('username', 'email'));
        }

        try {
            $brevoMailer = app(BrevoTransactionalMail::class);

            if ($brevoMailer->isConfigured()) {
                $brevoMailer->sendPasswordRecoveryCode($user, $recoveryCode, $expiresInMinutes);
            } else {
                $this->assertPasswordRecoveryMailConfiguration();
                Mail::to($user->email)->send(new PasswordRecoveryCodeMail($user, $recoveryCode, $expiresInMinutes));
            }
        } catch (Throwable $e) {
            $this->clearRecoveryCodeIfCurrent($user, $recoveryCodeHash);

            Log::error('Password recovery code email failed to send.', [
                'failure' => 'delivery',
                'username_hash' => hash('sha256', $validated['username']),
                'email_hash' => hash('sha256', $validated['email']),
                'mail_driver' => (string) config('mail.default'),
                'brevo_configured' => app(BrevoTransactionalMail::class)->isConfigured(),
                'smtp_host' => (string) config('mail.mailers.smtp.host'),
                'exception_type' => $e::class,
                'exception_message' => Str::limit($e->getMessage(), 500),
            ]);

            return redirect()->route('password.request')
                ->withErrors(['email' => 'The recovery code could not be delivered. Please verify the production email settings or contact the administrator.'])
                ->withInput($request->only('username', 'email'));
        }

        return redirect()->route('password.request')
            ->with('status', 'Recovery code sent to '.$this->maskRecoveryEmail($user->email).'. Check your inbox and spam folder.')
            ->with('recovery_code_sent', true)
            ->withInput($request->only('username', 'email'));
    }

    public function sendResetLink(Request $request)
    {
        $request->merge([
            'email' => $this->normalizeEmail($request->input('email')),
        ]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $eligibleUser = User::query()
            ->where('auth_method', User::AUTH_METHOD_LOCAL)
            ->whereNotNull('email_verified_at')
            ->whereRaw('LOWER(email) = ?', [$request->email])
            ->exists();

        if (! $eligibleUser) {
            return back()
                ->with('status', 'If a verified local account matches that email, a password reset link will be sent shortly.')
                ->onlyInput('email');
        }

        try {
            $status = Password::sendResetLink([
                'email' => $request->email,
                'auth_method' => User::AUTH_METHOD_LOCAL,
            ]);
        } catch (Throwable $e) {
            Log::error('Password reset email failed to send.', [
                'email_hash' => hash('sha256', strtolower((string) $request->email)),
                'exception_type' => $e::class,
            ]);

            return back()
                ->withErrors(['email' => 'We could not send the password reset email. Please try again later or contact support.'])
                ->onlyInput('email');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        // Do not reveal whether an email address belongs to an account.
        return back()
            ->with('status', 'If an account matches that email, a password reset link will be sent shortly.')
            ->onlyInput('email');
    }

    public function resetWithRecoveryCode(Request $request)
    {
        $request->merge([
            'username' => $this->normalizeUsername($request->input('username')),
            'recovery_code' => PasswordRecovery::normalize($request->input('recovery_code')),
        ]);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'recovery_code' => ['required', 'string', 'size:16'],
            'password' => $this->newPasswordRules(['confirmed']),
        ]);

        $rateLimitKey = 'password-recovery:'.sha1($validated['username'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()
                ->with('recovery_code_sent', true)
                ->withErrors(['recovery_code' => 'Too many recovery attempts. Please try again later.'])
                ->withInput(['username' => $validated['username']]);
        }

        RateLimiter::hit($rateLimitKey, 900);

        try {
            $user = DB::transaction(function () use ($validated): ?User {
                $user = User::query()
                    ->where('auth_method', User::AUTH_METHOD_LOCAL)
                    ->whereRaw('LOWER(username) = ?', [$validated['username']])
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $user
                    || PasswordRecovery::isExpired($user->password_recovery_code_expires_at)
                    || ! PasswordRecovery::check($validated['recovery_code'], $user->password_recovery_code_hash)
                ) {
                    return null;
                }

                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                    'password_recovery_code_hash' => null,
                    'password_recovery_code_generated_at' => null,
                    'password_recovery_code_expires_at' => null,
                ])->save();

                return $user;
            });
        } catch (Throwable $e) {
            Log::error('Username password recovery failed.', [
                'username_hash' => hash('sha256', $validated['username']),
                'exception_type' => $e::class,
            ]);

            return back()
                ->with('recovery_code_sent', true)
                ->withErrors(['recovery_code' => 'We could not reset your password right now. Please try again later.'])
                ->withInput(['username' => $validated['username']]);
        }

        if (! $user) {
            return back()
                ->with('recovery_code_sent', true)
                ->withErrors(['recovery_code' => 'The username or recovery code is incorrect.'])
                ->withInput(['username' => $validated['username']]);
        }

        RateLimiter::clear($rateLimitKey);
        event(new PasswordReset($user));

        return redirect('/')->with('success', 'Your password has been reset. You can now sign in with your username.');
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        return $this->mobileView('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->merge([
            'email' => $this->normalizeEmail($request->input('email')),
        ]);

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => $this->newPasswordRules(['confirmed']),
        ]);

        $status = Password::reset(
            array_merge(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                ['auth_method' => User::AUTH_METHOD_LOCAL]
            ),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect('/')->with('success', __($status));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $this->logAuthenticationActivity($user, 'user_logged_out', 'logged out', $request->ip());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function redirectToGoogle(Request $request)
    {
        return $this->redirectToGoogleWithIntent($request, 'login');
    }

    public function redirectToGoogleRegister(Request $request)
    {
        return $this->redirectToGoogleWithIntent($request, 'register');
    }

    public function redirectToGoogleAccountDeletion(Request $request)
    {
        if (! SystemSettings::userCan('delete_own_account')) {
            return redirect()->back()->withErrors([
                'account_delete' => 'Account deletion is currently disabled by the administrator.',
            ]);
        }

        $user = $request->user();

        if (! $user || ! $user->isGoogleAccount() || blank($user->google_id)) {
            return redirect()->back()->withErrors([
                'account_delete' => 'This account must be confirmed with its current password.',
            ]);
        }

        $request->session()->put(self::GOOGLE_ACCOUNT_DELETE_USER_SESSION_KEY, (int) $user->id);

        return $this->redirectToGoogleWithIntent($request, self::GOOGLE_ACCOUNT_DELETE_INTENT);
    }

    public function handleGoogleCallback(Request $request, AccountDeletionService $accountDeletionService)
    {
        $stateContext = $this->googleAuthStateContext($request);

        $expectedNonce = (string) $request->session()->pull(self::GOOGLE_AUTH_NONCE_SESSION_KEY, '');
        $returnedNonce = (string) ($stateContext['nonce'] ?? '');

        if (! app()->runningUnitTests()
            && ($expectedNonce === '' || $returnedNonce === '' || ! hash_equals($expectedNonce, $returnedNonce))) {
            $failedIntent = $this->googleAuthIntent($request, $stateContext['intent'] ?? null);

            Log::warning('Google authentication callback failed state validation.', [
                'has_state' => $request->filled('state'),
                'has_session_nonce' => $expectedNonce !== '',
            ]);

            return $this->googleAuthFailureResponse(
                $request,
                $failedIntent === self::GOOGLE_ACCOUNT_DELETE_INTENT
                    ? 'Google confirmation expired. Please start again.'
                    : 'Google sign-in expired. Please start Google login again.',
                $failedIntent
            );
        }

        $this->restoreGoogleLayoutPreference($request, $stateContext['layout'] ?? null);
        $intent = $this->googleAuthIntent($request, $stateContext['intent'] ?? null);

        if ($request->filled('error')) {
            Log::warning('Google authentication returned an OAuth error.', [
                'error' => $request->query('error'),
                'error_description' => $request->query('error_description'),
                'redirect_uri' => config('services.google.redirect'),
            ]);

            return $this->googleAuthFailureResponse($request, $this->googleOAuthErrorMessage($request), $intent);
        }

        if ($configurationError = $this->googleConfigurationErrorMessage()) {
            Log::warning('Google authentication is not configured correctly.');

            return $this->googleAuthFailureResponse($request, $configurationError, $intent);
        }

        if (! $request->filled('code')) {
            Log::warning('Google authentication callback was missing an authorization code.', [
                'query_keys' => array_keys($request->query()),
                'redirect_uri' => config('services.google.redirect'),
            ]);

            return $this->googleAuthFailureResponse($request, 'Google sign-in expired. Please start Google login again.', $intent);
        }

        try {
            AccountNotificationSchema::ensureUserColumns();

            $request->session()->forget(self::GOOGLE_AUTH_INTENT_SESSION_KEY);
            $driver = $this->googleDriver();
            $googleUser = $driver->user();
            $googleAvatarUrl = $this->googleAvatarUrl($googleUser);

            if (blank($googleUser->email)) {
                return $this->googleAuthFailureResponse($request, 'Google did not return an email address. Please try another Google account.', $intent);
            }

            $googleEmail = $this->normalizeEmail($googleUser->email);
            $googleVerifiedAt = now();

            if ($intent === self::GOOGLE_ACCOUNT_DELETE_INTENT) {
                return $this->completeGoogleAccountDeletion(
                    $request,
                    $googleUser,
                    $accountDeletionService
                );
            }

            $user = User::withTrashed()
                ->where('auth_method', User::AUTH_METHOD_GOOGLE)
                ->where('google_id', $googleUser->id)
                ->first();

            if ($user && $user->trashed() && $intent === 'login') {
                $user->restore();
            }

            if ($user && $intent === 'register') {
                return $this->googleAuthFailureResponse($request, 'This Google email is already registered. Please log in with Google instead.', $intent, [
                    'name' => $googleUser->name ?: 'Google User',
                    'email' => $googleEmail,
                ]);
            }

            if (
                ! $user
                && $intent === 'register'
                && User::withTrashed()
                    ->where('auth_method', User::AUTH_METHOD_GOOGLE)
                    ->whereRaw('LOWER(email) = ?', [$googleEmail])
                    ->exists()
            ) {
                return $this->googleAuthFailureResponse(
                    $request,
                    'This Google email is already registered. Please log in with Google instead.',
                    $intent,
                    ['email' => $googleEmail]
                );
            }

            if (! $user && $intent === 'login') {
                return $this->googleAuthFailureResponse($request, self::GOOGLE_LOGIN_UNREGISTERED_ACCOUNT_MESSAGE, $intent, [
                    'email' => $googleEmail,
                ], 'email', [
                    'auth_modal' => 'login',
                    'auth_alert' => self::GOOGLE_LOGIN_UNREGISTERED_ALERT_KEY,
                ]);
            }

            $registeredWithGoogle = false;

            if (! $user) {
                if (! Setting::enabled('acc_registration')) {
                    return $this->googleAuthFailureResponse($request, 'New account registration is currently disabled by the administrator.', $intent, [
                        'name' => $googleUser->name ?: 'Google User',
                        'email' => $googleEmail,
                    ]);
                }

                $userAttributes = [
                    'name' => $googleUser->name ?: 'Google User',
                    'email' => $googleEmail,
                    'google_id' => $googleUser->id,
                    'auth_method' => User::AUTH_METHOD_GOOGLE,
                    'password' => Hash::make(Str::random(64)),
                    'profile_photo_path' => $googleAvatarUrl,
                ];

                if ($this->hasUsernameColumn()) {
                    $userAttributes['username'] = $this->uniqueUsernameFromSeed($googleEmail ?: $googleUser->name ?: 'google-user');
                }

                $user = User::create($userAttributes);
                $user->forceFill([
                    'email_verified_at' => $googleVerifiedAt,
                ])->save();
                $registeredWithGoogle = true;

                $this->ensureAuthenticationProfile($user);

                $this->safeActivityLog(
                    $user,
                    'user_registered',
                    "{$user->name} ({$user->email}) registered a new account with Google.",
                    $request->ip(),
                    false
                );
            } else {
                $updates = [];
                if ($this->hasUsernameColumn() && blank($user->username)) {
                    $updates['username'] = $this->uniqueUsernameFromSeed($googleEmail ?: $googleUser->name ?: 'google-user');
                }
                if ($this->shouldSyncGoogleAvatar($user, $googleAvatarUrl)) {
                    $updates['profile_photo_path'] = $googleAvatarUrl;
                }
                if (! $user->email_verified_at && $this->normalizeEmail($user->email) === $googleEmail) {
                    $updates['email_verified_at'] = $googleVerifiedAt;
                }
                if (! empty($updates)) {
                    $user->forceFill($updates)->save();
                }
            }

            if (in_array($user->status, ['inactive', 'suspended'])) {
                return $this->googleAuthFailureResponse($request, 'Your account was inactivated please contact to the admin for request.', 'login', [
                    'email' => $user->email,
                ], 'account_inactive');
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            $this->logAuthenticationActivity($user, 'user_logged_in', 'logged in with Google', $request->ip());

            if ($user->is_admin) {
                return $this->redirectToRouteWithGoogleLayout($request, 'admin.dashboard');
            }

            if ($registeredWithGoogle) {
                return $this->redirectToRouteWithGoogleLayout($request, 'terms.acceptance.show')
                    ->with('success', 'Registration successful. Welcome to SpeakReady AI!')
                    ->with('registration_success', true);
            }

            if (! $user->hasAcceptedCurrentTerms()) {
                return $this->redirectToRouteWithGoogleLayout($request, 'terms.acceptance.show');
            }

            return $this->redirectToRouteWithGoogleLayout($request, 'dashboard');

        } catch (Throwable $e) {
            $message = $this->googleAuthenticationExceptionMessage($e);

            Log::error('Google authentication failed.', [
                'exception_type' => $e::class,
                'error_category' => $this->googleAuthenticationExceptionMessage($e),
                'friendly_message' => $message,
            ]);

            return $this->googleAuthFailureResponse($request, $message, $intent);
        }
    }

    private function redirectToGoogleWithIntent(Request $request, string $intent)
    {
        $intent = in_array($intent, self::GOOGLE_AUTH_INTENTS, true) ? $intent : 'login';
        $layout = $this->requestLayoutPreference($request) ?? 'desktop';

        $nonce = Str::random(40);

        $request->session()->put(self::GOOGLE_AUTH_INTENT_SESSION_KEY, $intent);
        $request->session()->put(self::GOOGLE_AUTH_LAYOUT_SESSION_KEY, $layout);
        $request->session()->put(self::GOOGLE_AUTH_NONCE_SESSION_KEY, $nonce);
        sr_remember_layout_preference($request, $layout);

        if ($configurationError = $this->googleConfigurationErrorMessage()) {
            Log::warning('Google authentication is not configured correctly.');

            return $this->googleAuthFailureResponse($request, $configurationError, $intent);
        }

        $parameters = [
            'state' => $this->googleAuthState($intent, $layout, $nonce),
            'prompt' => 'select_account',
        ];

        if ($intent === self::GOOGLE_ACCOUNT_DELETE_INTENT) {
            $parameters['max_age'] = 0;
        }

        return Socialite::driver('google')
            ->stateless()
            ->with($parameters)
            ->redirect();
    }

    private function completeGoogleAccountDeletion(
        Request $request,
        $googleUser,
        AccountDeletionService $accountDeletionService
    ) {
        $expectedUserId = (int) $request->session()->pull(
            self::GOOGLE_ACCOUNT_DELETE_USER_SESSION_KEY,
            0
        );
        $user = $request->user();

        if (! $user || ! $user->isGoogleAccount() || $expectedUserId < 1 || (int) $user->id !== $expectedUserId) {
            return $this->googleAuthFailureResponse(
                $request,
                'Account deletion confirmation expired. Sign in and try again.',
                self::GOOGLE_ACCOUNT_DELETE_INTENT
            );
        }

        if ($user->is_admin) {
            return $this->googleAuthFailureResponse(
                $request,
                'Administrator accounts cannot be deleted from the user account page.',
                self::GOOGLE_ACCOUNT_DELETE_INTENT
            );
        }

        if (! SystemSettings::userCan('delete_own_account')) {
            return $this->googleAuthFailureResponse(
                $request,
                'Account deletion is currently disabled by the administrator.',
                self::GOOGLE_ACCOUNT_DELETE_INTENT
            );
        }

        $expectedGoogleId = trim((string) $user->google_id);
        $selectedGoogleId = trim((string) ($googleUser->id ?? ''));

        if ($expectedGoogleId === ''
            || $selectedGoogleId === ''
            || ! hash_equals($expectedGoogleId, $selectedGoogleId)) {
            return $this->googleAuthFailureResponse(
                $request,
                'The selected Google account is not connected to this SpeakReady account. Choose the connected Google account and try again.',
                self::GOOGLE_ACCOUNT_DELETE_INTENT
            );
        }

        Auth::logout();

        try {
            $accountDeletionService->permanentlyDelete($user);
        } catch (Throwable $error) {
            Log::error('Permanent Google account deletion failed.', [
                'user_id' => $expectedUserId,
                'exception_type' => $error::class,
            ]);

            $remainingUser = User::query()->find($expectedUserId);
            if ($remainingUser) {
                Auth::login($remainingUser, true);
            }

            return $this->googleAuthFailureResponse(
                $request,
                'We could not delete your account right now. Please try again.',
                self::GOOGLE_ACCOUNT_DELETE_INTENT
            );
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your account and its data were permanently deleted.');
    }

    private function restoreGoogleLayoutPreference(Request $request, ?string $fallbackLayout = null): ?string
    {
        $layout = $this->explicitRequestLayoutPreference($request)
            ?? sr_normalized_layout($fallbackLayout)
            ?? sr_normalized_layout($request->session()->get(self::GOOGLE_AUTH_LAYOUT_SESSION_KEY))
            ?? sr_session_layout_preference($request);

        if (! in_array($layout, ['mobile', 'desktop'], true) && $request->attributes->has('is_mobile')) {
            $layout = (bool) $request->attributes->get('is_mobile') ? 'mobile' : 'desktop';
        }

        if (! in_array($layout, ['mobile', 'desktop'], true)) {
            return null;
        }

        $isMobile = $layout === 'mobile';
        $request->attributes->set('is_mobile', $isMobile);
        View::share('isMobile', $isMobile);
        $request->session()->put(self::GOOGLE_AUTH_LAYOUT_SESSION_KEY, $layout);
        sr_remember_layout_preference($request, $layout);

        return $layout;
    }

    private function requestLayoutPreference(Request $request): ?string
    {
        $layout = $this->explicitRequestLayoutPreference($request)
            ?? sr_session_layout_preference($request);

        if ($layout !== null) {
            return $layout;
        }

        if ($request->attributes->has('is_mobile')) {
            return (bool) $request->attributes->get('is_mobile') ? 'mobile' : 'desktop';
        }

        return null;
    }

    private function explicitRequestLayoutPreference(Request $request): ?string
    {
        $queryLayout = strtolower((string) $request->query('sr_layout', ''));
        if (in_array($queryLayout, ['mobile', 'desktop'], true)) {
            return $queryLayout;
        }

        $queryIsMobile = strtolower((string) $request->query('sr_is_mobile', ''));
        if (in_array($queryIsMobile, ['1', 'true'], true)) {
            return 'mobile';
        }
        if (in_array($queryIsMobile, ['0', 'false'], true)) {
            return 'desktop';
        }

        $queryViewportWidth = filter_var($request->query('sr_viewport_width'), FILTER_VALIDATE_INT);
        if ($queryViewportWidth !== false && $queryViewportWidth > 0) {
            return $queryViewportWidth <= self::MOBILE_VIEWPORT_MAX ? 'mobile' : 'desktop';
        }

        return null;
    }

    private function googleLayoutQuery(Request $request): array
    {
        $layout = $this->requestLayoutPreference($request)
            ?? $request->session()->get(self::GOOGLE_AUTH_LAYOUT_SESSION_KEY);

        return $layout === 'mobile'
            ? ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']
            : [];
    }

    private function redirectToHomeWithGoogleLayout(Request $request, array $extraQuery = [])
    {
        $query = array_merge($this->googleLayoutQuery($request), $extraQuery);

        return empty($query)
            ? redirect('/')
            : redirect('/?'.http_build_query($query));
    }

    private function redirectToRouteWithGoogleLayout(Request $request, string $routeName, array $parameters = [])
    {
        return redirect()->route($routeName, array_merge($parameters, $this->googleLayoutQuery($request)));
    }

    private function googleAuthIntent(Request $request, ?string $fallbackIntent = null): string
    {
        $intent = in_array($fallbackIntent, self::GOOGLE_AUTH_INTENTS, true)
            ? $fallbackIntent
            : $request->session()->get(self::GOOGLE_AUTH_INTENT_SESSION_KEY, 'login');

        return in_array($intent, self::GOOGLE_AUTH_INTENTS, true) ? $intent : 'login';
    }

    private function googleAuthFailureResponse(Request $request, string $message, ?string $intent = null, array $input = [], string $field = 'email', array $homeQuery = [])
    {
        $intent = $this->googleAuthIntent($request, $intent);

        if ($intent === self::GOOGLE_ACCOUNT_DELETE_INTENT) {
            $request->session()->forget(self::GOOGLE_ACCOUNT_DELETE_USER_SESSION_KEY);

            $redirect = Auth::check()
                ? $this->redirectToRouteWithGoogleLayout($request, 'user.account')
                : $this->redirectToHomeWithGoogleLayout($request, $homeQuery);

            return $redirect->withErrors(['account_delete' => $message]);
        }

        return $this->redirectToHomeWithGoogleLayout($request, $homeQuery)
            ->withErrors([$field => $message])
            ->withInput($this->googleAuthOldInput($request, $intent, $input));
    }

    private function googleAuthOldInput(Request $request, ?string $intent, array $input): array
    {
        $intent = in_array($intent, ['login', 'register'], true)
            ? $intent
            : $this->googleAuthIntent($request);

        return array_merge([
            self::AUTH_TAB_OLD_INPUT_KEY => $intent === 'register' ? 'signup' : 'login',
        ], $input);
    }

    private function googleAuthState(string $intent, string $layout, string $nonce): string
    {
        $payload = [
            'intent' => in_array($intent, self::GOOGLE_AUTH_INTENTS, true) ? $intent : 'login',
            'layout' => in_array($layout, ['mobile', 'desktop'], true) ? $layout : 'desktop',
            'issued_at' => time(),
            'nonce' => $nonce,
        ];

        $body = rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $body, $this->googleAuthStateKey());

        return 'sr.'.$body.'.'.$signature;
    }

    private function googleAuthStateContext(Request $request): array
    {
        $state = trim((string) $request->query('state', ''));
        if (! Str::startsWith($state, 'sr.')) {
            return [];
        }

        $parts = explode('.', $state, 3);
        if (count($parts) !== 3 || $parts[0] !== 'sr') {
            return [];
        }

        [, $body, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $body, $this->googleAuthStateKey());
        if (! hash_equals($expectedSignature, $signature)) {
            return [];
        }

        $json = base64_decode(strtr($body, '-_', '+/'), true);
        $payload = is_string($json) ? json_decode($json, true) : null;
        if (! is_array($payload)) {
            return [];
        }

        $issuedAt = filter_var($payload['issued_at'] ?? null, FILTER_VALIDATE_INT);
        if ($issuedAt === false || $issuedAt < time() - 3600) {
            return [];
        }

        return [
            'intent' => in_array($payload['intent'] ?? null, self::GOOGLE_AUTH_INTENTS, true) ? $payload['intent'] : null,
            'layout' => sr_normalized_layout($payload['layout'] ?? null),
            'nonce' => is_string($payload['nonce'] ?? null) ? $payload['nonce'] : null,
        ];
    }

    private function googleAuthStateKey(): string
    {
        $key = (string) config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            $decoded = base64_decode(Str::after($key, 'base64:'), true);

            return is_string($decoded) && $decoded !== '' ? $decoded : $key;
        }

        return $key;
    }

    private function googleAvatarUrl($googleUser): ?string
    {
        $avatar = trim((string) ($googleUser->avatar
            ?: data_get($googleUser->user ?? [], 'picture')
            ?: ($googleUser->avatar_original ?? '')));

        if ($avatar === '') {
            return null;
        }

        if (Str::startsWith($avatar, '//')) {
            $avatar = 'https:'.$avatar;
        }

        if (! Str::startsWith($avatar, ['http://', 'https://', 'data:'])) {
            return null;
        }

        return $avatar;
    }

    private function shouldSyncGoogleAvatar(User $user, ?string $googleAvatarUrl): bool
    {
        if (! $googleAvatarUrl) {
            return false;
        }

        $currentPhoto = trim((string) $user->profile_photo_path);

        return $currentPhoto === '' || $this->isGoogleHostedAvatar($currentPhoto);
    }

    private function isGoogleHostedAvatar(string $photoPath): bool
    {
        if (Str::startsWith($photoPath, '//')) {
            $photoPath = 'https:'.$photoPath;
        }

        if (! Str::startsWith($photoPath, ['http://', 'https://'])) {
            return false;
        }

        $host = parse_url($photoPath, PHP_URL_HOST);

        return is_string($host)
            && Str::contains(Str::lower($host), ['googleusercontent.com', 'google.com']);
    }

    private function googleDriver()
    {
        $driver = Socialite::driver('google')->stateless();

        $connectTimeout = (float) config('services.google.connect_timeout', 5);
        $timeout = (float) config('services.google.timeout', 20);
        $guzzleOptions = [
            'connect_timeout' => max(5, $connectTimeout),
            'timeout' => max(20, $timeout),
            'read_timeout' => max(20, $timeout),
        ];

        $curlOptions = [];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        if (defined('CURLOPT_NOSIGNAL')) {
            $curlOptions[CURLOPT_NOSIGNAL] = 1;
        }
        if (! empty($curlOptions)) {
            $guzzleOptions['curl'] = $curlOptions;
        }

        if (app()->environment('local')) {
            $guzzleOptions['verify'] = false;
        }

        $driver->setHttpClient(new GuzzleClient($guzzleOptions));

        return $driver;
    }

    private function googleConfigurationErrorMessage(): ?string
    {
        $required = [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect' => config('services.google.redirect'),
        ];

        foreach ($required as $value) {
            if (blank($value)) {
                return 'Google sign-in is not configured correctly. Please contact the administrator.';
            }
        }

        return null;
    }

    private function googleOAuthErrorMessage(Request $request): string
    {
        $error = trim((string) $request->query('error', ''));

        if ($error === 'access_denied') {
            return 'Google sign-in was cancelled. Please choose a Google account and allow access to continue.';
        }

        return 'Google could not authorize this request. Please start Google sign-in again.';
    }

    private function googleAuthenticationExceptionMessage(Throwable $e): string
    {
        $message = Str::lower($e->getMessage());

        if (Str::contains($message, [
            'curl error 6',
            'curl error 7',
            'curl error 28',
            'could not resolve host',
            'failed to connect',
            'timed out',
            'timeout',
        ])) {
            return 'Google sign-in could not reach Google in time. Please check your connection and try again.';
        }

        if (Str::contains($message, [
            'authorization code',
            'bad_verification_code',
            'invalid_grant',
            'invalid_request',
            'missing required parameter: code',
        ])) {
            return 'Google sign-in expired. Please start Google login again.';
        }

        if (Str::contains($message, ['redirect_uri_mismatch', 'redirect uri', 'redirect_uri'])) {
            return 'Google sign-in is using the wrong callback URL. Please contact the administrator.';
        }

        if (Str::contains($message, ['invalid_client', 'unauthorized_client', 'client secret'])) {
            return 'Google sign-in is not configured correctly. Please contact the administrator.';
        }

        return 'Google authentication could not be completed. Please start Google login again.';
    }

    private function normalizeEmail(mixed $email): string
    {
        return Str::lower(trim((string) $email));
    }

    private function assertPasswordRecoverySchemaReady(): void
    {
        foreach ([
            'password_recovery_code_hash',
            'password_recovery_code_generated_at',
            'password_recovery_code_expires_at',
        ] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                throw new \RuntimeException("The users.{$column} recovery column is missing.");
            }
        }
    }

    private function assertPasswordRecoveryMailConfiguration(): void
    {
        $mailer = (string) config('mail.default');

        if (app()->environment('production') && in_array($mailer, ['array', 'log'], true)) {
            throw new \RuntimeException("The {$mailer} mailer does not deliver email.");
        }

        if ($mailer === 'smtp' && blank(config('mail.mailers.smtp.host'))) {
            throw new \RuntimeException('SMTP host is not configured.');
        }

        if ($mailer === 'smtp' && Str::contains(Str::lower((string) config('mail.mailers.smtp.host')), 'gmail')) {
            if (blank(config('mail.mailers.smtp.username')) || blank(config('mail.mailers.smtp.password'))) {
                throw new \RuntimeException('Gmail SMTP username or App Password is not configured.');
            }
        }

        if (blank(config('mail.from.address'))) {
            throw new \RuntimeException('Mail from address is not configured.');
        }
    }

    private function clearRecoveryCodeIfCurrent(User $user, string $recoveryCodeHash): void
    {
        try {
            User::query()
                ->whereKey($user->getKey())
                ->where('password_recovery_code_hash', $recoveryCodeHash)
                ->update([
                    'password_recovery_code_hash' => null,
                    'password_recovery_code_generated_at' => null,
                    'password_recovery_code_expires_at' => null,
                ]);
        } catch (Throwable $e) {
            Log::critical('Password recovery cleanup failed after email delivery failure.', [
                'failure' => 'delivery_cleanup',
                'user_id' => $user->getKey(),
                'exception_type' => $e::class,
                'exception_message' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    private function maskRecoveryEmail(?string $email): string
    {
        $email = $this->normalizeEmail($email);
        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($localPart === '' || $domain === '') {
            return 'your recovery email';
        }

        $visibleLength = min(2, strlen($localPart));

        return substr($localPart, 0, $visibleLength)
            .str_repeat('*', max(3, strlen($localPart) - $visibleLength))
            .'@'.$domain;
    }

    private function normalizeUsername(mixed $username): string
    {
        return Str::lower(trim((string) $username));
    }

    private function findUserByLoginIdentifier(string $identifier): ?User
    {
        $identifier = $this->normalizeUsername($identifier);

        return User::query()
            ->where('auth_method', User::AUTH_METHOD_LOCAL)
            ->when(
                $this->hasUsernameColumn(),
                fn ($query) => $query->where('username', $identifier),
                fn ($query) => $query->whereRaw('LOWER(email) = ?', [$identifier])
            )
            ->first();
    }

    private function findUserForReactivation(string $identifier): ?User
    {
        $user = $this->findUserByLoginIdentifier($identifier);

        if ($user || ! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $user;
        }

        return User::query()
            ->where('auth_method', User::AUTH_METHOD_GOOGLE)
            ->whereRaw('LOWER(email) = ?', [$this->normalizeEmail($identifier)])
            ->first();
    }

    private function hasUsernameColumn(): bool
    {
        try {
            return Schema::hasColumn('users', 'username');
        } catch (Throwable) {
            return false;
        }
    }

    private function uniqueUsernameFromSeed(string $seed): string
    {
        $base = $this->usernameBase($seed);
        $candidate = $base;
        $suffix = 2;

        while (User::where('username', $candidate)->exists()) {
            $candidate = Str::limit($base, 55, '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function usernameBase(string $seed): string
    {
        $seed = Str::before($seed, '@');
        $seed = Str::ascii(Str::lower($seed));
        $seed = preg_replace('/[^a-z0-9._-]+/', '-', $seed) ?: '';
        $seed = trim($seed, '.-_');

        if (strlen($seed) < 3) {
            $seed = 'user-'.$seed;
        }

        return Str::limit($seed, 60, '');
    }

    private function failedLoginResponse(string $field, string $message)
    {
        return back()->withErrors([
            $field => $message,
        ])->onlyInput('username');
    }

    private function loginFailureMessage(string $specificMessage): string
    {
        return $this->genericLoginErrorsEnabled()
            ? self::LOGIN_CREDENTIALS_MISMATCH_MESSAGE
            : $specificMessage;
    }

    private function genericLoginErrorsEnabled(): bool
    {
        return filter_var(env('AUTH_GENERIC_LOGIN_ERRORS', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function newPasswordRules(array $extra = []): array
    {
        $rule = PasswordRule::min(8);

        if (SystemSettings::enabled('sec_strong_pass', false)) {
            $rule = $rule->mixedCase()->numbers()->symbols();
        }

        return array_merge(['required', 'string', $rule], $extra);
    }

    private function loginLockoutMessage(Request $request, string $email): ?string
    {
        $seconds = Cache::get($this->loginLockoutCacheKey($request, $email));
        if (! $seconds) {
            return null;
        }

        return 'Too many login attempts. Please try again in a few minutes.';
    }

    private function recordFailedLoginAttempt(Request $request, string $email): void
    {
        $limit = max(1, (int) SystemSettings::value('sec_login_limit', 5));
        $lockoutMinutes = max(1, (int) SystemSettings::value('sec_lockout', 15));
        $attemptKey = $this->loginAttemptCacheKey($request, $email);
        $attempts = (int) Cache::get($attemptKey, 0) + 1;

        Cache::put($attemptKey, $attempts, now()->addMinutes($lockoutMinutes));

        if ($attempts >= $limit) {
            Cache::put($this->loginLockoutCacheKey($request, $email), $lockoutMinutes * 60, now()->addMinutes($lockoutMinutes));
        }
    }

    private function clearFailedLoginAttempts(Request $request, string $email): void
    {
        Cache::forget($this->loginAttemptCacheKey($request, $email));
        Cache::forget($this->loginLockoutCacheKey($request, $email));
    }

    private function loginAttemptCacheKey(Request $request, string $email): string
    {
        return 'login_attempts:'.sha1(Str::lower($email).'|'.$request->ip());
    }

    private function loginLockoutCacheKey(Request $request, string $email): string
    {
        return 'login_lockout:'.sha1(Str::lower($email).'|'.$request->ip());
    }

    private function logAuthenticationActivity(User $user, string $action, string $activity, ?string $ipAddress): void
    {
        $this->safeActivityLog(
            $user,
            $action,
            "{$user->name} ({$this->userIdentifier($user)}) {$activity}.",
            $ipAddress,
            false
        );
    }

    private function userIdentifier(User $user): string
    {
        return filled($user->username)
            ? (string) $user->username
            : (string) $user->email;
    }

    private function ensureAuthenticationProfile(User $user): void
    {
        try {
            if (! Schema::hasTable('profiles')) {
                return;
            }

            Profile::forUser((int) $user->id);
        } catch (Throwable $e) {
            Log::warning('Unable to create authentication profile.', [
                'user_id' => $user->id,
                'exception_type' => $e::class,
            ]);
        }
    }

    private function safeActivityLog(User $user, string $action, string $description, ?string $ipAddress, bool $notify): void
    {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return;
            }

            ActivityLogger::log($user, $action, $description, $ipAddress, $notify);
        } catch (Throwable $e) {
            Log::warning('Unable to write authentication activity.', [
                'user_id' => $user->id,
                'action' => $action,
                'exception' => $e,
            ]);
        }
    }
}
