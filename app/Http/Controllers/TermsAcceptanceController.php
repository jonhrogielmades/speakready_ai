<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsAcceptanceController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasAcceptedCurrentTerms()) {
            return redirect()->intended(route('dashboard'));
        }

        if ($request->session()->has('registration_success') || $request->session()->has('success')) {
            $request->session()->reflash();
        }

        return $this->mobileView('terms.accept', [
            'termsVersion' => User::TERMS_VERSION,
            'lastUpdated' => 'September 23, 2026',
            'sections' => $this->termsSections(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'terms_accepted' => ['accepted'],
        ], [
            'terms_accepted.accepted' => 'Please check the agreement box before continuing.',
        ]);

        $registrationSuccess = (bool) $request->session()->get('registration_success', false);
        $successMessage = $request->session()->get('success');

        $request->user()->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => User::TERMS_VERSION,
            'terms_ip_address' => $request->ip(),
        ])->save();

        $redirect = redirect()->intended(route('dashboard'));

        if ($registrationSuccess) {
            return $redirect
                ->with('registration_success', true)
                ->with('success', $successMessage ?: 'Registration successful. Welcome to SpeakReady AI!');
        }

        return $redirect->with('success', 'Terms accepted. You can now use your dashboard.');
    }

    private function termsSections(): array
    {
        return [
            [
                'icon' => 'fa-solid fa-user-check',
                'heading' => 'Use of the Service',
                'body' => 'SpeakReady AI is designed for interview preparation, learning, and self-improvement. You are responsible for the information you submit and for using feedback appropriately.',
            ],
            [
                'icon' => 'fa-solid fa-wand-magic-sparkles',
                'heading' => 'AI Feedback',
                'body' => 'AI-generated feedback is coaching support. It is not a hiring decision, academic decision, legal opinion, or guarantee of interview results.',
            ],
            [
                'icon' => 'fa-solid fa-user-shield',
                'heading' => 'Account Responsibility',
                'body' => 'Keep your account credentials secure, provide accurate account information, and sign out on shared devices.',
            ],
            [
                'icon' => 'fa-solid fa-rotate',
                'heading' => 'Updates',
                'body' => 'These terms may be updated as the service changes. If a future update requires renewed acceptance, you will be asked before continuing into the app.',
            ],
        ];
    }
}
