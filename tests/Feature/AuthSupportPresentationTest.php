<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthSupportPresentationTest extends TestCase
{
    public function test_recovery_pages_use_the_shared_auth_presentation(): void
    {
        $supportMarkup = file_get_contents(base_path('resources/views/shared/auth-support-page.blade.php'));
        $recoverySubmit = strpos($supportMarkup, 'Send Recovery Code');
        $supportSwitch = strpos($supportMarkup, '<span>Ready to sign in?</span>');

        $this->assertNotFalse($recoverySubmit);
        $this->assertNotFalse($supportSwitch);
        $this->assertGreaterThan($recoverySubmit, $supportSwitch, 'The recovery sign-in switch should sit below the recovery action.');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('auth-support-page', false)
            ->assertSee('css/shared/auth-page.css?v=18', false)
            ->assertSee('authPageThemeToggle', false)
            ->assertSee('Send Recovery Code');

        $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'person@example.com',
        ]))
            ->assertOk()
            ->assertSee('auth-support-page', false)
            ->assertSee('Create a new password')
            ->assertSee('data-password-strength', false)
            ->assertSee('authPageThemeToggle', false);
    }

    public function test_legal_pages_use_the_shared_auth_presentation(): void
    {
        foreach ([route('legal.privacy'), route('legal.terms')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('legal-page-shell', false)
                ->assertSee('css/shared/auth-page.css?v=18', false)
                ->assertSee('css/shared/legal-page.css?v=1', false)
                ->assertSee('authPageThemeToggle', false);
        }
    }
}
