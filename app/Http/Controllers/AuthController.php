<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        if (! Setting::enabled('acc_registration')) {
            return back()->withErrors([
                'email' => 'New account registration is currently disabled by the administrator.',
            ])->withInput($request->only('name', 'email'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
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
            "{$user->name} ({$user->email}) registered a new account.",
            $request->ip(),
            false
        );

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        $rememberDevice = $request->boolean('remember', true);

        if (Auth::attempt($credentials, $rememberDevice)) {
            $user = Auth::user();
            
            if (in_array($user->status, ['inactive', 'suspended'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'account_inactive' => 'Your account was inactivated please contact to the admin for request.',
                ])->withInput($request->only('email'));
            }

            $request->session()->regenerate();
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
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && in_array($user->status, ['inactive', 'suspended'])) {
            $user->update(['reactivation_requested_at' => now()]);

            ActivityLogger::log(
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

    public function redirectToGoogle()
    {
        return \Laravel\Socialite\Facades\Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $driver = \Laravel\Socialite\Facades\Socialite::driver('google')->stateless();
            
            // Fix for Render 504 Gateway Timeout: Force IPv4 and add connection timeouts
            $guzzleOptions = [
                'timeout' => 15,
                'connect_timeout' => 5,
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ]
            ];
            
            // Disable SSL verification for local development (Laragon)
            if (app()->environment('local')) {
                $guzzleOptions['verify'] = false;
            }
            
            $driver->setHttpClient(new \GuzzleHttp\Client($guzzleOptions));
            
            $googleUser = $driver->user();
            
            $user = User::withTrashed()
                ->where(function ($query) use ($googleUser) {
                    $query->where('google_id', $googleUser->id)
                          ->orWhere('email', $googleUser->email);
                })->first();

            if ($user && $user->trashed()) {
                // Automatically restore the user's account if they log back in with Google
                $user->restore();
            }

            if (!$user) {
                if (! Setting::enabled('acc_registration')) {
                    return redirect('/')->withErrors([
                        'email' => 'New account registration is currently disabled by the administrator.',
                    ])->withInput(['email' => $googleUser->email]);
                }

                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null, // No password for Google login
                    'profile_photo_path' => $googleUser->avatar,
                ]);

                // Create profile for SpeakReady AI features
                Profile::create([
                    'user_id' => $user->id,
                    'readiness_score' => 0,
                    'total_sessions' => 0,
                ]);
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

            Auth::login($user, true);

            // Send an email notification for the Google login
            // Temporarily disabled to prevent 504 Gateway Timeout if SMTP is not configured or blocked on Render
            // $user->notify(new \App\Notifications\GoogleLoginAlert());

            if (in_array($user->status, ['inactive', 'suspended'])) {
                Auth::logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return redirect('/')->withErrors([
                    'account_inactive' => 'Your account was inactivated please contact to the admin for request.',
                ])->withInput(['email' => $user->email]);
            }

            $this->logAuthenticationActivity($user, 'user_logged_in', 'logged in with Google', request()->ip());

            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google login failed: ' . $e->getMessage(), ['exception' => $e]);
            return redirect('/')->withErrors(['email' => 'Failed to login with Google: ' . $e->getMessage()]);
        }
    }

    private function logAuthenticationActivity(User $user, string $action, string $activity, ?string $ipAddress): void
    {
        ActivityLogger::log(
            $user,
            $action,
            "{$user->name} ({$user->email}) {$activity}.",
            $ipAddress,
            false
        );
    }
}
