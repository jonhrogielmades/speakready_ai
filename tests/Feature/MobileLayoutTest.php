<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dashboard_uses_mobile_shell_for_mobile_user_agent(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($user)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="mob-content"', false)
            ->assertSee('--mob-card-gap: 12px', false)
            ->assertSee('.tracker-panel', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_dashboard_uses_consistent_mobile_card_rhythm_for_mobile_user_agent(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="mob-content"', false)
            ->assertSee('--mob-card-gap: 12px', false)
            ->assertSee('.premium-card', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_user_dashboard_keeps_desktop_shell_for_desktop_user_agent(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->actingAs($user)
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="db-sidebar"', false)
            ->assertDontSee('id="mob-bottom-nav"', false);
    }

    public function test_user_dashboard_uses_mobile_shell_from_viewport_cookie(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withUnencryptedCookie('sr_is_mobile', '1')
            ->withUnencryptedCookie('sr_viewport_width', '390')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="mob-content"', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_user_mobile_shell_includes_movable_quick_navigation_destinations(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('class="ucp-mobile-launcher"', false)
            ->assertSee('id="userCommandPalette"', false)
            ->assertSee('id="userCommandList"', false)
            ->assertSee('id="ucp-destination-dashboard"', false)
            ->assertSee('id="ucp-destination-account"', false)
            ->assertSee('14 destinations')
            ->assertDontSee('data-ucp-search', false);

        $launcherScript = file_get_contents(public_path('js/user-ui.js'));

        $this->assertIsString($launcherScript);
        $this->assertStringContainsString('setupMovableLauncher', $launcherScript);
        $this->assertStringContainsString('snapToNearestEdge', $launcherScript);
        $this->assertStringContainsString('speakready.ucp-launcher-position.v1', $launcherScript);
    }
}
