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
}
