<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthSupportPresentationTest extends TestCase
{
    public function test_recovery_pages_use_the_shared_auth_presentation(): void
    {
        $supportMarkup = file_get_contents(base_path('resources/views/shared/auth-support-page.blade.php'));
        $recoverySubmit = strpos($supportMarkup, 'Send Recovery Code');
        $supportSwitch = strrpos($supportMarkup, '<span>Return to login?</span>');

        $this->assertNotFalse($recoverySubmit);
        $this->assertNotFalse($supportSwitch);
        $this->assertGreaterThan($recoverySubmit, $supportSwitch, 'The recovery sign-in switch should sit below the recovery action.');

        $recoveryMarkup = $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('auth-support-page', false)
            ->assertSee('css/shared/auth-page.css?v=33', false)
            ->assertSee('authPageThemeToggle', false)
            ->assertSee('Send Recovery Code');

        $this->assertStringNotContainsString('auth-page-support-footer', $recoveryMarkup->getContent());

        $resetMarkup = $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'person@example.com',
        ]))
            ->assertOk()
            ->assertSee('auth-support-page', false)
            ->assertSee('Create a new password')
            ->assertSee('data-password-strength', false)
            ->assertSee('authPageThemeToggle', false);

        $this->assertStringNotContainsString('auth-page-support-footer', $resetMarkup->getContent());
    }

    public function test_auth_pages_do_not_autofocus_inputs_on_navigation(): void
    {
        $userAgents = [
            'mobile' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36',
        ];
        $authPages = [
            route('login'),
            route('password.request'),
            route('password.reset', [
                'token' => 'test-token',
                'email' => 'person@example.com',
            ]),
        ];

        foreach ($userAgents as $device => $userAgent) {
            foreach ($authPages as $url) {
                $markup = $this->withHeader('User-Agent', $userAgent)
                    ->get($url)
                    ->assertOk()
                    ->getContent();

                $this->assertIsString($markup);
                $this->assertStringNotContainsString('autofocus', $markup, ucfirst($device).' auth pages should wait for an explicit user tap before opening the keyboard.');
            }
        }
    }

    public function test_legal_pages_use_the_shared_auth_presentation(): void
    {
        foreach ([route('legal.privacy'), route('legal.terms')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('legal-page-shell', false)
            ->assertSee('css/shared/auth-page.css?v=33', false)
                ->assertSee('css/shared/legal-page.css?v=1', false)
                ->assertSee('authPageThemeToggle', false);
        }
    }
}
