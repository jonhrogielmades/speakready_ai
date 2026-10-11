<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use App\Support\AccountNotificationSchema;
use App\Support\SystemSettings;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    private const LOGIN_USERNAME_MISMATCH_MESSAGE = 'The username does not match our records.';

    private const LOGIN_PASSWORD_MISMATCH_MESSAGE = 'The password do not match our records.';

    private const LOGIN_CREDENTIALS_MISMATCH_MESSAGE = 'The provided credentials do not match our records.';

    private const GOOGLE_AUTH_INTENT_SESSION_KEY = 'google_auth_intent';

    private const GOOGLE_AUTH_LAYOUT_SESSION_KEY = 'google_auth_layout';

    private const AUTH_TAB_OLD_INPUT_KEY = 'auth_tab';

    private const MOBILE_VIEWPORT_MAX = 820;

    public function register(Request $request)
    {
        if (! Setting::enabled('acc_registration')) {
            return back()->withErrors([
                'username' => 'New account registration is currently disabled by the administrator.',
            ])->withInput($request->only('name', 'username', self::AUTH_TAB_OLD_INPUT_KEY));
        }

        $request->merge([
            'username' => $this->normalizeUsername($request->input('username')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'min:3', 'max:64', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'password' => $this->newPasswordRules(['confirmed']),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => null,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $this->ensureAuthenticationProfile($user);

        Auth::login($user, true);

        $this->safeActivityLog(
            $user,
            'user_registered',
            "{$user->name} ({$this->userIdentifier($user)}) registered a new account.",
            $request->ip(),
            false
        );

        $registrationMessage = 'Registration successful. Welcome to SpeakReady AI!';

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard', sr_layout_query($request))
                ->with('success', $registrationMessage)
                ->with('registration_success', true);
        }

        return redirect()->route('terms.acceptance.show', sr_layout_query($request))
            ->with('success', $registrationMessage)
            ->with('registration_success', true);
    }

    public function login(Request $request)
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
            $message = $this->passwordMatchesAnyUser($validated['password'])
                ? self::LOGIN_USERNAME_MISMATCH_MESSAGE
                : self::LOGIN_CREDENTIALS_MISMATCH_MESSAGE;

            $this->recordFailedLoginAttempt($request, $identifier);

            return $this->failedLoginResponse('username', $message);
        }

        if (! Hash::check($validated['password'], (string) $user->password)) {
            $this->recordFailedLoginAttempt($request, $identifier);

            return $this->failedLoginResponse('password', self::LOGIN_PASSWORD_MISMATCH_MESSAGE);
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

            if (! $user->is_admin && filled($user->email) && SystemSettings::enabled('acc_verify_email', false) && ! $user->email_verified_at) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'Email verification is required before you can sign in.',
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

        $user = $this->findUserByLoginIdentifier($validated['username']);

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

    public function sendResetLink(Request $request)
    {
        $request->merge([
            'email' => $this->normalizeEmail($request->input('email')),
        ]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            Log::error('Password reset email failed to send.', [
                'email' => $request->email,
                'exception' => $e,
            ]);

            return back()
                ->withErrors(['email' => 'We could not send the password reset email. Please try again later or contact support.'])
                ->onlyInput('email');
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
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
            $request->only('email', 'password', 'password_confirmation', 'token'),
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

    public function handleGoogleCallback(Request $request)
    {
        $stateContext = $this->googleAuthStateContext($request);
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

            $user = User::withTrashed()
                ->where('google_id', $googleUser->id)
                ->first();

            if (! $user) {
                $user = User::withTrashed()
                    ->where('email', $googleEmail)
                    ->first();
            }

            if ($user && $user->trashed() && $intent === 'login') {
                $user->restore();
            }

            if ($user && $intent === 'register') {
                return $this->googleAuthFailureResponse($request, 'This Google email is already registered. Please log in with Google instead.', $intent, [
                    'name' => $googleUser->name ?: 'Google User',
                    'email' => $googleEmail,
                ]);
            }

            if (! $user && $intent === 'login') {
                return $this->googleAuthFailureResponse($request, 'No SpeakReady AI account was found for this Google email. Please register first.', $intent, [
                    'email' => $googleEmail,
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
                if (! $user->google_id) {
                    $updates['google_id'] = $googleUser->id;
                }
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

            Log::error('Google authentication failed: '.$e->getMessage(), [
                'exception' => $e,
                'friendly_message' => $message,
            ]);

            return $this->googleAuthFailureResponse($request, $message, $intent);
        }
    }

    private function redirectToGoogleWithIntent(Request $request, string $intent)
    {
        $layout = $this->requestLayoutPreference($request) ?? 'desktop';

        $request->session()->put(self::GOOGLE_AUTH_INTENT_SESSION_KEY, $intent);
        $request->session()->put(self::GOOGLE_AUTH_LAYOUT_SESSION_KEY, $layout);
        sr_remember_layout_preference($request, $layout);

        if ($configurationError = $this->googleConfigurationErrorMessage()) {
            Log::warning('Google authentication is not configured correctly.');

            return $this->googleAuthFailureResponse($request, $configurationError, $intent);
        }

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $this->googleAuthState($intent, $layout)])
            ->redirect();
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

        return $layout === 'mobile' ? ['sr_layout' => 'mobile'] : [];
    }

    private function redirectToHomeWithGoogleLayout(Request $request)
    {
        $query = $this->googleLayoutQuery($request);

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
        $intent = in_array($fallbackIntent, ['login', 'register'], true)
            ? $fallbackIntent
            : $request->session()->get(self::GOOGLE_AUTH_INTENT_SESSION_KEY, 'login');

        return in_array($intent, ['login', 'register'], true) ? $intent : 'login';
    }

    private function googleAuthFailureResponse(Request $request, string $message, ?string $intent = null, array $input = [], string $field = 'email')
    {
        return $this->redirectToHomeWithGoogleLayout($request)
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

    private function googleAuthState(string $intent, string $layout): string
    {
        $payload = [
            'intent' => in_array($intent, ['login', 'register'], true) ? $intent : 'login',
            'layout' => in_array($layout, ['mobile', 'desktop'], true) ? $layout : 'desktop',
            'issued_at' => time(),
            'nonce' => Str::random(12),
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
            'intent' => in_array($payload['intent'] ?? null, ['login', 'register'], true) ? $payload['intent'] : null,
            'layout' => sr_normalized_layout($payload['layout'] ?? null),
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

    private function normalizeUsername(mixed $username): string
    {
        return Str::lower(trim((string) $username));
    }

    private function findUserByLoginIdentifier(string $identifier): ?User
    {
        $identifier = $this->normalizeUsername($identifier);

        return User::query()
            ->where(function ($query) use ($identifier): void {
                if ($this->hasUsernameColumn()) {
                    $query->where('username', $identifier)
                        ->orWhereRaw('LOWER(email) = ?', [$identifier]);

                    return;
                }

                $query->whereRaw('LOWER(email) = ?', [$identifier]);
            })
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

    private function passwordMatchesAnyUser(string $password): bool
    {
        foreach (User::whereNotNull('password')->select('password')->cursor() as $user) {
            if (Hash::check($password, (string) $user->password)) {
                return true;
            }
        }

        return false;
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

            Profile::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'readiness_score' => 0,
                'total_sessions' => 0,
            ]);
        } catch (Throwable $e) {
            Log::warning('Unable to create authentication profile.', [
                'user_id' => $user->id,
                'exception' => $e,
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
