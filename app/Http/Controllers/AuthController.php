<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Setting;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
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
                'email' => 'New account registration is currently disabled by the administrator.',
            ])->withInput($request->only('name', 'email'));
        }

        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $this->ensureAuthenticationProfile($user);

        Auth::login($user, true);

        $this->safeActivityLog(
            $user,
            'user_registered',
            "{$user->name} ({$user->email}) registered a new account.",
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
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $rememberDevice = $request->boolean('remember', true);
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials, $rememberDevice)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (in_array($user->status, ['inactive', 'suspended'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'account_inactive' => 'Your account was inactivated please contact to the admin for request.',
                ])->withInput([
                    'email' => $validated['email'],
                ]);
            }

            $this->logAuthenticationActivity($user, 'user_logged_in', 'logged in', $request->ip());

            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function requestReactivation(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user && in_array($user->status, ['inactive', 'suspended'])) {
            $user->update(['reactivation_requested_at' => now()]);

            $this->safeActivityLog(
                $user,
                'reactivation_requested',
                "{$user->name} ({$user->email}) requested account reactivation.",
                $request->ip(),
                false
            );

            return back()->with('success', 'Your reactivation request has been sent to the admin.');
        }

        return back()->withErrors([
            'email' => 'Unable to process your request at this time.',
        ])->onlyInput('email');
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
        try {
            $intent = $request->session()->pull('google_auth_intent', 'login');
            $intent = in_array($intent, ['login', 'register'], true) ? $intent : 'login';
            $driver = $this->googleDriver();
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
                    'name' => $googleUser->name ?: 'Google User',
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null,
                    'profile_photo_path' => $googleUser->avatar,
                ]);

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
                if (!$user->google_id) {
                    $updates['google_id'] = $googleUser->id;
                }
                if (!$user->profile_photo_path) {
                    $updates['profile_photo_path'] = $googleUser->avatar;
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

        } catch (Throwable $e) {
            Log::error('Google authentication failed: ' . $e->getMessage(), ['exception' => $e]);

            return redirect('/')->withErrors([
                'email' => 'Google authentication took too long or could not be completed. Please try again.',
            ]);
        }
    }

    private function redirectToGoogleWithIntent(Request $request, string $intent)
    {
        $request->session()->put('google_auth_intent', $intent);

        return Socialite::driver('google')->stateless()->redirect();
    }

    private function googleDriver()
    {
        $driver = Socialite::driver('google')->stateless();

        $guzzleOptions = [
            'connect_timeout' => (float) config('services.google.connect_timeout', 3),
            'timeout' => (float) config('services.google.timeout', 8),
            'read_timeout' => (float) config('services.google.timeout', 8),
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

    private function logAuthenticationActivity(User $user, string $action, string $activity, ?string $ipAddress): void
    {
        $this->safeActivityLog(
            $user,
            $action,
            "{$user->name} ({$user->email}) {$activity}.",
            $ipAddress,
            false
        );
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
