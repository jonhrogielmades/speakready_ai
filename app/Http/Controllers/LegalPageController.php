<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function privacy(): View
    {
        return $this->render(
            'Privacy Policy',
            'Privacy',
            'How SpeakReady AI handles account, interview, and contact information.',
            [
                ['heading' => 'Information We Collect', 'body' => 'We collect the account details, contact form messages, interview setup details, practice answers, feedback records, and basic usage information needed to operate the service.'],
                ['heading' => 'How We Use Information', 'body' => 'We use this information to authenticate users, run mock interviews, generate feedback, provide support, improve product quality, and protect the platform from abuse.'],
                ['heading' => 'Sharing and Retention', 'body' => 'Interview records are private by default. Shared review links are controlled by the user settings available in the product. We keep information only as long as needed for the service, security, and legal requirements.'],
                ['heading' => 'Contact', 'body' => 'For privacy questions, contact admin@speakready.ai.'],
            ]
        );
    }

    public function terms(): View
    {
        return $this->render(
            'Terms of Service',
            'Terms',
            'The basic terms for using SpeakReady AI interview preparation tools.',
            [
                ['heading' => 'Use of the Service', 'body' => 'SpeakReady AI is designed for interview preparation, learning, and self-improvement. Users are responsible for the information they submit and for using feedback appropriately.'],
                ['heading' => 'AI Feedback', 'body' => 'AI-generated feedback is coaching support, not a hiring decision, academic decision, legal opinion, or guarantee of interview results.'],
                ['heading' => 'Accounts', 'body' => 'Users must keep account credentials secure and provide accurate information when creating or maintaining an account.'],
                ['heading' => 'Updates', 'body' => 'These terms may be updated as the service changes. Continued use of the platform means accepting the current terms.'],
            ]
        );
    }

    public function security(): View
    {
        return $this->render(
            'Security',
            'Security',
            'How SpeakReady AI protects accounts, interview data, and shared reviews.',
            [
                ['heading' => 'Account Protection', 'body' => 'Passwords are handled through the application authentication system, and password reset flows use secure reset links.'],
                ['heading' => 'Private Records', 'body' => 'Interview records are private unless a user chooses to share a review link. Shared links can be protected by the options available in the product.'],
                ['heading' => 'Operational Safeguards', 'body' => 'The platform validates user input, separates admin and user areas, and records important activity for support and audit needs.'],
                ['heading' => 'Report an Issue', 'body' => 'Send security concerns to admin@speakready.ai with enough detail for the team to investigate.'],
            ]
        );
    }

    public function cookies(): View
    {
        return $this->render(
            'Cookie Preferences',
            'Cookies',
            'How SpeakReady AI uses small browser storage preferences.',
            [
                ['heading' => 'Essential Preferences', 'body' => 'SpeakReady AI uses browser storage for essentials such as theme choice, viewport preference, install prompt dismissal, and authentication session behavior.'],
                ['heading' => 'Functional Storage', 'body' => 'Functional preferences help the interface remember display mode and avoid showing repeated prompts in the same browsing context.'],
                ['heading' => 'Managing Preferences', 'body' => 'You can clear stored preferences from your browser settings. Signing out clears server-side authentication, while browser storage is managed by your browser.'],
                ['heading' => 'Support', 'body' => 'For help with cookies or stored preferences, contact admin@speakready.ai.'],
            ]
        );
    }

    private function render(string $title, string $kicker, string $intro, array $sections): View
    {
        return view('legal.show', compact('title', 'kicker', 'intro', 'sections'));
    }
}
