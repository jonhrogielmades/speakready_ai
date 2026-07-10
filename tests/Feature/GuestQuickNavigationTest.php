<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestQuickNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_layout_uses_the_movable_destination_quick_navigation(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('class="ucp-mobile-launcher"', false)
            ->assertSee('data-ucp-context="guest"', false)
            ->assertSee('data-ucp-storage-key="speakready.guest-quick-nav-position.v1"', false)
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
            ->assertDontSee('data-ucp-search', false)
            ->assertDontSee('id="guestQuickNavLauncher"', false)
            ->assertDontSee('id="mbmenu"', false);

        $markup = $response->getContent();

        $this->assertSame(2, substr_count($markup, 'data-ucp-open'));
        $this->assertSame(7, substr_count($markup, 'data-ucp-item'));
        $this->assertSame(2, substr_count($markup, 'data-ucp-action'));

        $launcherScript = file_get_contents(public_path('js/user-ui.js'));

        $this->assertIsString($launcherScript);
        $this->assertStringContainsString('launcher.dataset.ucpStorageKey', $launcherScript);
        $this->assertStringContainsString("document.getElementById('nbar')", $launcherScript);
        $this->assertStringContainsString('snapToNearestEdge', $launcherScript);
        $this->assertStringContainsString("addEventListener('pointermove'", $launcherScript);
        $this->assertStringContainsString('setTriggerExpanded', $launcherScript);
        $this->assertStringContainsString('destination.focus({ preventScroll: true })', $launcherScript);
        $this->assertStringContainsString("addEventListener('hidden.bs.offcanvas'", $launcherScript);
        $this->assertStringContainsString('focusReturn.focus({ preventScroll: true })', $launcherScript);
    }
}
