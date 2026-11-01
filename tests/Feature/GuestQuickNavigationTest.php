<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestQuickNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_layout_uses_accessible_shared_landing_navigation(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('class="sr-skip-link" href="#main-content"', false)
            ->assertSee('<main id="main-content" tabindex="-1">', false)
            ->assertSee('id="guestNavToggle"', false)
            ->assertSee('aria-controls="guestNavPanel"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('id="guestNavPanel"', false)
            ->assertSee('aria-label="Mobile navigation"', false)
            ->assertSee('href="#how"', false)
            ->assertSee('href="#features"', false)
            ->assertSee('href="#sample-feedback"', false)
            ->assertSee('href="#faq"', false)
            ->assertSee('Practice your next interview.')
            ->assertSee('Start practicing free')
            ->assertSee('id="sample-feedback"', false)
            ->assertSee('id="trust"', false)
            ->assertSee('id="faq"', false)
            ->assertSee(route('login.form'))
            ->assertSee(route('register.form'))
            ->assertSee('css/shared/landing-page.css?v=1', false)
            ->assertSee('js/landing-page.js?v=1', false)
            ->assertDontSee('id="userCommandPalette"', false)
            ->assertDontSee('data-ucp-open', false)
            ->assertDontSee('data-bs-target="#lofc"', false)
            ->assertDontSee('id="loginForm"', false)
            ->assertDontSee('id="signupForm"', false)
            ->assertDontSee(route('auth.google.login'))
            ->assertDontSee(route('auth.google.register'))
            ->assertDontSee('css/bootstrap.min.css', false)
            ->assertDontSee('css/all.min.css', false)
            ->assertDontSee('css/swiper-bundle.min.css', false)
            ->assertDontSee('js/bootstrap.bundle.min.js', false)
            ->assertDontSee('js/swiper-bundle.min.js', false)
            ->assertDontSee('js/main.js', false)
            ->assertDontSee('js/user-ui.js', false);

        $markup = $response->getContent();

        $this->assertSame(1, substr_count($markup, 'id="guestNavToggle"'));
        $this->assertSame(1, substr_count($markup, 'id="guestNavPanel"'));
        $this->assertSame(1, substr_count($markup, 'js/landing-page.js?v=1'));
        $this->assertMatchesRegularExpression(
            '/id="guestNavToggle".*?aria-expanded="false".*?aria-controls="guestNavPanel"/s',
            $markup
        );

        $landingScript = file_get_contents(public_path('js/landing-page.js'));

        $this->assertIsString($landingScript);
        $this->assertStringContainsString('toggle.addEventListener("click"', $landingScript);
        $this->assertStringContainsString('event.key !== "Escape"', $landingScript);
        $this->assertStringContainsString('setOpen(false, true)', $landingScript);
        $this->assertStringContainsString('if (restoreFocus) toggle.focus()', $landingScript);
        $this->assertStringContainsString('installButton.addEventListener("click"', $landingScript);
        $this->assertStringContainsString('window.addEventListener("beforeinstallprompt"', $landingScript);
        $this->assertStringContainsString('deferredInstallPrompt = event;', $landingScript);
        $this->assertStringContainsString('returnFocusTo.focus()', $landingScript);
        $this->assertStringNotContainsString('queuePwaInstallPrompt', $landingScript);
        $this->assertStringNotContainsString('PWA_PROMPT_AUTO_SHOWN', $landingScript);
    }
}
