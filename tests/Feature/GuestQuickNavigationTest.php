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
            ->assertDontSee('id="gqn-destination-features"', false)
            ->assertDontSee('id="gqn-destination-benefits"', false)
            ->assertSee('id="gqn-destination-about"', false)
            ->assertSee('id="gqn-destination-how"', false)
            ->assertSee('id="gqn-destination-developers"', false)
            ->assertDontSee('id="gqn-destination-faq"', false)
            ->assertDontSee('id="gqn-destination-contact"', false)
            ->assertSee('class="ucp-guest-actions"', false)
            ->assertSee('data-ucp-action', false)
            ->assertSee('class="guest-brand-copy"', false)
            ->assertSee('id="guestHeaderClock"', false)
            ->assertSee('id="guestHeaderDate"', false)
            ->assertSee('id="guestHeaderTime"', false)
            ->assertSee('Start Practicing', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('css/desktop/guest.css?v=23', false)
            ->assertSee('js/guest.js?v=3', false)
            ->assertDontSee('js/main.js', false)
            ->assertDontSee('js/user-ui.js', false)
            ->assertDontSee('id="lofc"', false)
            ->assertDontSee('data-bs-target="#lofc"', false)
            ->assertDontSee('swTab(', false)
            ->assertDontSee('ui-mobile-wire', false)
            ->assertDontSee('id="features"', false)
            ->assertDontSee('id="benefits"', false)
            ->assertDontSee('landing-stats-row', false)
            ->assertDontSee('data-landing-stat=', false)
            ->assertSee('href="#about" class="boc btn', false)
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
        $this->assertSame(4, substr_count($markup, 'data-ucp-item'));
        $this->assertSame(2, substr_count($markup, 'data-ucp-action'));
        $this->assertSame(0, substr_count($markup, 'js/user-ui.js'));
        $this->assertSame(1, substr_count($markup, 'js/guest.js'));
        $this->assertSame(0, substr_count($markup, 'js/main.js'));
        $this->assertStringContainsString(
            'class="ucp-guest-action ucp-guest-login" href="'.route('login').'" data-ucp-action',
            $markup
        );
        $this->assertStringContainsString(
            'class="ucp-guest-action ucp-guest-register" href="'.route('register').'" data-ucp-action',
            $markup
        );
        $this->assertMatchesRegularExpression(
            '/id="mbtog".*?data-ucp-open.*?aria-controls="userCommandPalette".*?<i class="fa-solid fa-bars"/s',
            $markup
        );

        $guestScript = file_get_contents(public_path('js/guest.js'));

        $this->assertIsString($guestScript);
        $this->assertStringContainsString('setupGuestCommandPalette', $guestScript);
        $this->assertStringContainsString('setTriggerExpanded', $guestScript);
        $this->assertStringContainsString('destination.focus({ preventScroll: true })', $guestScript);
        $this->assertStringContainsString('lastFocusedElement.focus({ preventScroll: true })', $guestScript);
        $this->assertStringContainsString('setupGuestHeaderClock', $guestScript);
        $this->assertStringContainsString('new Intl.DateTimeFormat', $guestScript);
        $this->assertStringContainsString('millisecondsUntilNextMinute', $guestScript);
        $this->assertStringContainsString('clock.dateTime = now.toISOString()', $guestScript);
        $this->assertStringNotContainsString('setupViewportMetrics', $guestScript);
        $this->assertStringNotContainsString('SpeakReadyViewport', $guestScript);
        $this->assertStringNotContainsString("'features'", $guestScript);
        $this->assertStringNotContainsString("'benefits'", $guestScript);
        $this->assertStringNotContainsString('swTab(', $guestScript);
        $this->assertStringNotContainsString('#lofc', $guestScript);
    }
}
