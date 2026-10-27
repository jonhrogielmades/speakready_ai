<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOnboardingProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsAcceptanceController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        sr_remember_layout_preference($request);

        if ($user->hasAcceptedCurrentTerms()) {
            return redirect()->intended(route('dashboard', sr_layout_query($request)));
        }

        if ($request->session()->has('registration_success') || $request->session()->has('success')) {
            $request->session()->reflash();
        }

        $onboardingProgress = UserOnboardingProgress::forUser($user);

        return $this->mobileView('terms.accept', [
            'termsVersion' => User::TERMS_VERSION,
            'lastUpdated' => 'September 23, 2026',
            'sections' => $this->termsSections(),
            'recoveryCodeAcknowledgementRequired' => $onboardingProgress->requiresRecoveryCodeAcknowledgement(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $onboardingProgress = UserOnboardingProgress::forUser($user);
        $registrationSuccess = (bool) $request->session()->get('registration_success', false);
        $recoveryCodeAcknowledgementRequired = $onboardingProgress->requiresRecoveryCodeAcknowledgement();
        $completingRegistration = $registrationSuccess || $recoveryCodeAcknowledgementRequired;

        if ($registrationSuccess) {
            // Preserve the one-time recovery code if validation sends the user
            // back to the agreement screen.
            $request->session()->reflash();
        }

        $rules = [
            'terms_accepted' => ['accepted'],
        ];
        $messages = [
            'terms_accepted.accepted' => 'Please check the agreement box before continuing.',
        ];

        if ($recoveryCodeAcknowledgementRequired) {
            $rules['recovery_code_saved'] = ['accepted'];
            $messages['recovery_code_saved.accepted'] = 'Please confirm that you saved your recovery code before continuing.';
        }

        $request->validate($rules, $messages);

        $user->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => User::TERMS_VERSION,
            'terms_ip_address' => $request->ip(),
        ])->save();

        if ($recoveryCodeAcknowledgementRequired) {
            $onboardingProgress->recovery_code_saved_at ??= now();
        }
        $onboardingProgress->syncFromUserState($user);

        sr_remember_layout_preference($request);

        $redirect = redirect()->intended(route('dashboard', sr_layout_query($request)));

        if ($completingRegistration) {
            // The registration confirmation and recovery code were already shown
            // on the terms page, so do not repeat them on the dashboard.
            $request->session()->forget(['registration_success', 'success', 'recovery_code']);

            return $redirect;
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
