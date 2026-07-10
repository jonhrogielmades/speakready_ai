<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestQuickNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_layout_uses_the_header_destination_quick_navigation(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="mbtog"', false)
            ->assertSee('id="userCommandPalette"', false)
            ->assertSee('id="userCommandList"', false)
            ->assertSee('id="gqn-destination-home"', false)
            ->assertSee('id="gqn-destination-features"', false)
            ->assertSee('id="gqn-destination-how"', false)
            ->assertSee('id="gqn-destination-benefits"', false)
            ->assertSee('id="gqn-destination-developers"', false)
            ->assertSee('id="gqn-destination-faq"', false)
            ->assertSee('id="gqn-destination-contact"', false)
            ->assertSee('class="ucp-guest-actions"', false)
            ->assertSee('data-ucp-action', false)
            ->assertSee('class="guest-brand-copy"', false)
            ->assertSee('id="guestHeaderClock"', false)
            ->assertSee('id="guestHeaderDate"', false)
            ->assertSee('id="guestHeaderTime"', false)
            ->assertSee('Start Practicing', false)
            ->assertSee('onclick="swTab(\'login\')"', false)
            ->assertDontSee('swTab(\'signin\')', false)
            ->assertDontSee('data-ucp-search', false)
            ->assertDontSee('class="ucp-mobile-launcher"', false)
            ->assertDontSee('data-ucp-context="guest"', false)
            ->assertDontSee('speakready.guest-quick-nav-position.v1', false)
            ->assertDontSee('id="ucpMobileLauncherHelp"', false)
            ->assertDontSee('id="ucpMobileLauncherStatus"', false)
            ->assertDontSee('id="guestQuickNavLauncher"', false)
            ->assertDontSee('id="mbmenu"', false);

        $markup = $response->getContent();

        $this->assertSame(1, substr_count($markup, 'data-ucp-open'));
        $this->assertSame(7, substr_count($markup, 'data-ucp-item'));
        $this->assertSame(2, substr_count($markup, 'data-ucp-action'));
        $this->assertMatchesRegularExpression(
            '/id="mbtog".*?data-ucp-open.*?aria-controls="userCommandPalette".*?<i class="fa-solid fa-bars"/s',
            $markup
        );

        $paletteScript = file_get_contents(public_path('js/user-ui.js'));

        $this->assertIsString($paletteScript);
        $this->assertStringContainsString('setTriggerExpanded', $paletteScript);
        $this->assertStringContainsString('destination.focus({ preventScroll: true })', $paletteScript);
        $this->assertStringContainsString("addEventListener('hidden.bs.offcanvas'", $paletteScript);
        $this->assertStringContainsString('focusReturn.focus({ preventScroll: true })', $paletteScript);

        $mainScript = file_get_contents(public_path('js/main.js'));

        $this->assertIsString($mainScript);
        $this->assertStringContainsString('setupGuestHeaderClock', $mainScript);
        $this->assertStringContainsString('new Intl.DateTimeFormat', $mainScript);
        $this->assertStringContainsString('millisecondsUntilNextMinute', $mainScript);
        $this->assertStringContainsString('clock.dateTime = now.toISOString()', $mainScript);
        $this->assertStringContainsString("t === 'login' || t === 'signin'", $mainScript);
        $this->assertStringContainsString('loginTab?.classList.toggle', $mainScript);
    }
}
