<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Setting;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        if (! Setting::enabled('acc_registration')) {
            return back()->withErrors([
                'identifier' => 'New account registration is currently disabled by the administrator.',
            ])->withInput($request->only('name', 'identifier', 'username', 'email'));
        }

        $submittedIdentifier = trim((string) $request->input('identifier'));
        $submittedUsername = trim((string) $request->input('username'));
        $submittedEmail = trim((string) $request->input('email'));

        if ($submittedIdentifier !== '') {
            $identifier = $submittedIdentifier;
            $email = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? Str::lower($identifier)
                : null;
            $username = $email
                ? User::generateUniqueUsernameFrom($email)
                : User::normalizeUsername($identifier);
        } else {
            $identifier = $submittedUsername ?: $submittedEmail;
            $email = $submittedEmail !== '' ? Str::lower($submittedEmail) : null;
            $username = $submittedUsername !== ''
                ? User::normalizeUsername($submittedUsername)
                : ($email ? User::generateUniqueUsernameFrom($email) : '');
        }

        $request->merge([
            'identifier' => $identifier,
            'username' => $username,
            'email' => $email,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255',
            'username' => 'required|string|min:3|max:30|regex:/^[a-z0-9_]+$/|unique:users,username',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'username.regex' => 'Username may only contain letters, numbers, and underscores.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Create profile for SpeakReady AI features
        Profile::create([
            'user_id' => $user->id,
            'readiness_score' => 0,
            'total_sessions' => 0,
        ]);

        Auth::login($user, true);

        ActivityLogger::log(
            $user,
            'user_registered',
            "{$user->name} ({$this->accountIdentifier($user)}) registered a new account.",
            $request->ip(),
            false
        );

        $registrationMessage = 'Registration successful. Welcome to SpeakReady AI!';

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard')
                ->with('success', $registrationMessage)
                ->with('registration_success', true);
        }

        return redirect()->route('dashboard')
            ->with('success', $registrationMessage)
            ->with('registration_success', true);
    }

    public function login(Request $request)
    {
        $identifier = trim((string) $request->input('login', $request->input('email')));
        $request->merge(['login' => $identifier]);

        $validated = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $rememberDevice = $request->boolean('remember', true);
        $loginField = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $loginValue = $loginField === 'username'
            ? User::normalizeUsername($validated['login'])
            : Str::lower($validated['login']);
        $user = User::whereRaw("LOWER({$loginField}) = ?", [$loginValue])->first();

        if ($user && is_string($user->password) && Hash::check($validated['password'], $user->password)) {
            if (in_array($user->status, ['inactive', 'suspended'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'account_inactive' => 'Your account was inactivated please contact to the admin for request.',
                ])->withInput([
                    'login' => $validated['login'],
                    'email' => $user->email,
                ]);
            }

            Auth::login($user, $rememberDevice);
            $request->session()->regenerate();
            $this->logAuthenticationActivity($user, 'user_logged_in', 'logged in', $request->ip());

            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function requestReactivation(Request $request)
    {
        $identifier = trim((string) $request->input('login', $request->input('email')));
        $request->merge(['login' => $identifier]);

        $validated = $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $loginField = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $loginValue = $loginField === 'username'
            ? User::normalizeUsername($validated['login'])
            : Str::lower($validated['login']);
        $user = User::whereRaw("LOWER({$loginField}) = ?", [$loginValue])->first();

        if ($user && in_array($user->status, ['inactive', 'suspended'])) {
            $user->update(['reactivation_requested_at' => now()]);

            ActivityLogger::log(
                $user,
                'reactivation_requested',
                "{$user->name} ({$this->accountIdentifier($user)}) requested account reactivation.",
                $request->ip(),
                false
            );

            return back()->with('success', 'Your reactivation request has been sent to the admin.');
        }

        return back()->withErrors([
            'login' => 'Unable to process your request at this time.',
        ])->onlyInput('login');
    }

    public function showForgotPasswordForm()
    {
        return $this->mobileView('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
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
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
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

        return $status === Password::PASSWORD_RESET
            ? redirect('/')->with('success', __($status))
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
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
        if ($request->filled('error')) {
            Log::warning('Google authentication returned an OAuth error.', [
                'error' => $request->query('error'),
                'error_description' => $request->query('error_description'),
                'redirect_uri' => $this->googleRedirectUrl($request),
            ]);

            return redirect('/')->withErrors([
                'email' => $this->googleOAuthErrorMessage($request),
            ]);
        }

        if (! $request->filled('code')) {
            Log::warning('Google authentication callback did not include an authorization code.', [
                'redirect_uri' => $this->googleRedirectUrl($request),
            ]);

            return redirect('/')->withErrors([
                'email' => 'Google did not return an authorization code. Please start Google sign-in again.',
            ]);
        }

        try {
            $intent = $request->session()->pull('google_auth_intent', 'login');
            $intent = in_array($intent, ['login', 'register'], true) ? $intent : 'login';
            $driver = $this->googleDriver($request);
            $googleUser = $driver->user();

            if (blank($googleUser->email)) {
                return redirect('/')->withErrors([
                    'email' => 'Google did not return an email address. Please try another Google account.',
                ]);
            }

            $user = User::withTrashed()
                ->where('google_id', $googleUser->id)
                ->first();

            if (! $user) {
                $user = User::withTrashed()
                    ->where('email', $googleUser->email)
                    ->first();
            }

            if ($user && $user->trashed() && $intent === 'login') {
                $user->restore();
            }

            if ($user && $intent === 'register') {
                return redirect('/')->withErrors([
                    'email' => 'This Google email is already registered. Please log in with Google instead.',
                ])->withInput([
                    'name' => $googleUser->name ?: 'Google User',
                    'email' => $googleUser->email,
                ]);
            }

            if (! $user && $intent === 'login') {
                return redirect('/')->withErrors([
                    'email' => 'No SpeakReady AI account was found for this Google email. Please register first.',
                ])->withInput(['email' => $googleUser->email]);
            }

            if (! $user) {
                if (! Setting::enabled('acc_registration')) {
                    return redirect('/')->withErrors([
                        'email' => 'New account registration is currently disabled by the administrator.',
                    ])->withInput([
                        'name' => $googleUser->name ?: 'Google User',
                        'email' => $googleUser->email,
                    ]);
                }

                $user = User::create([
                    'name' => $googleUser->name,
                    'username' => User::generateUniqueUsernameFrom($googleUser->email ?: $googleUser->name ?: 'google_user'),
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null,
                    'profile_photo_path' => $googleUser->avatar,
                ]);

                Profile::firstOrCreate([
                    'user_id' => $user->id,
                ], [
                    'readiness_score' => 0,
                    'total_sessions' => 0,
                ]);

                ActivityLogger::log(
                    $user,
                    'user_registered',
                    "{$user->name} ({$user->email}) registered a new account with Google.",
                    $request->ip(),
                    false
                );
            } else {
                $updates = [];
                if (!$user->google_id) {
                    $updates['google_id'] = $googleUser->id;
                }
                if (!$user->profile_photo_path) {
                    $updates['profile_photo_path'] = $googleUser->avatar;
                }
                if (!$user->username) {
                    $updates['username'] = User::generateUniqueUsernameFrom($googleUser->email ?: $googleUser->name ?: 'google_user', $user->id);
                }
                if (!empty($updates)) {
                    $user->update($updates);
                }
            }

            if (in_array($user->status, ['inactive', 'suspended'])) {
                return redirect('/')->withErrors([
                    'account_inactive' => 'Your account was inactivated please contact to the admin for request.',
                ])->withInput(['email' => $user->email]);
            }

            Auth::login($user, true);

            $this->logAuthenticationActivity($user, 'user_logged_in', 'logged in with Google', $request->ip());

            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('dashboard');

        } catch (ConnectException $e) {
            Log::error('Google authentication timed out while contacting Google.', [
                'message' => $e->getMessage(),
                'redirect_uri' => $this->googleRedirectUrl($request),
            ]);

            return redirect('/')->withErrors([
                'email' => 'Google authentication could not reach Google in time. Please check your connection and try again.',
            ]);
        } catch (RequestException $e) {
            Log::error('Google authentication token request failed.', [
                'message' => $e->getMessage(),
                'status' => $e->getResponse()?->getStatusCode(),
                'body' => $this->googleErrorResponseBody($e),
                'redirect_uri' => $this->googleRedirectUrl($request),
            ]);

            return redirect('/')->withErrors([
                'email' => $this->googleRequestExceptionMessage($e),
            ]);
        } catch (Throwable $e) {
            Log::error('Google authentication failed: ' . $e->getMessage(), ['exception' => $e]);

            return redirect('/')->withErrors([
                'email' => 'Google authentication could not be completed. Please try again.',
            ]);
        }
    }

    private function redirectToGoogleWithIntent(Request $request, string $intent)
    {
        $request->session()->put('google_auth_intent', $intent);

        return $this->googleDriver($request)->redirect();
    }

    private function googleDriver(Request $request)
    {
        $driver = Socialite::driver('google')
            ->stateless()
            ->redirectUrl($this->googleRedirectUrl($request));

        $guzzleOptions = [
            'connect_timeout' => (float) config('services.google.connect_timeout', 10),
            'timeout' => (float) config('services.google.timeout', 30),
            'read_timeout' => (float) config('services.google.timeout', 30),
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

    private function googleRedirectUrl(Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/') . route('auth.google.callback', [], false);
    }

    private function googleOAuthErrorMessage(Request $request): string
    {
        return match ((string) $request->query('error')) {
            'access_denied' => 'Google sign-in was cancelled. Please choose your Google account again to continue.',
            'redirect_uri_mismatch' => 'Google sign-in is using the wrong callback URL. Please open SpeakReady AI from its localhost or Render URL and try again.',
            default => 'Google sign-in could not be completed. Please try again.',
        };
    }

    private function googleRequestExceptionMessage(RequestException $e): string
    {
        $body = $this->googleErrorResponseBody($e);

        if (Str::contains($body, ['redirect_uri_mismatch', 'redirect_uri'])) {
            return 'Google sign-in is using the wrong callback URL. Please open SpeakReady AI from its localhost or Render URL and try again.';
        }

        if (Str::contains($body, ['invalid_grant', 'Bad Request'])) {
            return 'Google could not verify this sign-in request. Please start Google sign-in again from the same app address.';
        }

        return 'Google authentication could not be completed. Please try again.';
    }

    private function googleErrorResponseBody(RequestException $e): string
    {
        $response = $e->getResponse();

        if (! $response) {
            return '';
        }

        $body = (string) $response->getBody();

        return Str::limit($body, 1000);
    }

    private function logAuthenticationActivity(User $user, string $action, string $activity, ?string $ipAddress): void
    {
        ActivityLogger::log(
            $user,
            $action,
            "{$user->name} ({$this->accountIdentifier($user)}) {$activity}.",
            $ipAddress,
            false
        );
    }

    private function accountIdentifier(User $user): string
    {
        return $user->email ?: $user->username ?: (string) $user->id;
    }
}
