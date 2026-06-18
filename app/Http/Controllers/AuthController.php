<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
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

        Auth::login($user);

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            if (Auth::user()->is_admin) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function redirectToGoogle()
    {
        return \Laravel\Socialite\Facades\Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')->user();
            
            $user = User::where('google_id', $googleUser->id)->orWhere('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => null, // No password for Google login
                ]);

                // Create profile for SpeakReady AI features
                Profile::create([
                    'user_id' => $user->id,
                    'readiness_score' => 0,
                    'total_sessions' => 0,
                ]);
            } elseif (!$user->google_id) {
                // Link the google_id if the user exists but hasn't logged in with Google before
                $user->update(['google_id' => $googleUser->id]);
            }

            Auth::login($user);

            if ($user->is_admin) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            return redirect('/')->withErrors(['email' => 'Failed to login with Google.']);
        }
    }
}
