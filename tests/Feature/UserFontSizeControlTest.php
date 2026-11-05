<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFontSizeControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_font_size_control_is_present_in_both_user_headers(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        foreach (['desktop', 'mobile'] as $layout) {
            $request = $this->actingAs($user);
            if ($layout === 'mobile') {
                $request = $request->withHeader('User-Agent', $mobileUserAgent);
            }

            $response = $request->get(route('dashboard'));
            $response->assertOk()
                ->assertSee('data-font-size-control', false)
                ->assertSee('data-font-size-toggle', false)
                ->assertSee('data-font-size-menu', false)
                ->assertSee('data-font-size-action="decrease"', false)
                ->assertSee('data-font-size-action="reset"', false)
                ->assertSee('data-font-size-action="increase"', false)
                ->assertSee('js/user-font-size.js?v=3', false)
                ->assertSee('js/font-size-boot.js?v=2', false)
                ->assertSee('css/shared/user-font-size.css?v=2', false)
                ->assertSee('id="'.($layout === 'mobile' ? 'mob' : 'db').'FontSizeBtn"', false);

            if (getenv('SR_FONT_SIZE_BROWSER_SNAPSHOT') === '1') {
                $directory = storage_path('app/font-size-browser');
                if (! is_dir($directory)) {
                    mkdir($directory, 0775, true);
                }
                file_put_contents($directory.'/'.$layout.'.html', $response->getContent());
            }
        }
    }
}
