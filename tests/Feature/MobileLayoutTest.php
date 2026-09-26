<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_landing_uses_new_mobile_guest_layout_for_mobile_user_agents(): void
    {
        $mobileUserAgents = [
            'iPhone 13 Safari' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'Android Chrome' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
        ];

        foreach ($mobileUserAgents as $label => $userAgent) {
            $this->withHeader('User-Agent', $userAgent)
                ->get('/')
                ->assertOk()
                ->assertSee('guest-shell guest-mobile-shell', false)
                ->assertSee('data-layout-shell="mobile"', false)
                ->assertSee('data-guest-layout="mobile"', false)
                ->assertSee('css/mobile/style.css?v=30', false)
                ->assertSee('mobilePreviewSwiper', false)
                ->assertSee('mobile-preview-image-swiper', false)
                ->assertSee('id="mbtog"', false)
                ->assertSee('aria-controls="userCommandPalette"', false)
                ->assertSee('id="userCommandPalette"', false)
                ->assertSee('data-ucp-open', false)
                ->assertSee('id="guestHeaderClock"', false)
                ->assertDontSee('aria-controls="mbmenu"', false)
                ->assertDontSee('id="mbmenu"', false)
                ->assertDontSee('id="barIcon"', false)
                ->assertDontSee('id="xIcon"', false)
                ->assertDontSee('css/desktop/style.css?v=7', false);
        }
    }

    public function test_guest_landing_uses_mobile_guest_layout_from_viewport_cookie(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->withUnencryptedCookie('sr_is_mobile', '1')
            ->withUnencryptedCookie('sr_viewport_width', '390')
            ->get('/')
            ->assertOk()
            ->assertSee('guest-shell guest-mobile-shell', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('css/mobile/style.css?v=30', false)
            ->assertSee('mobilePreviewSwiper', false)
            ->assertDontSee('css/desktop/style.css?v=7', false);
    }

    public function test_guest_landing_uses_mobile_guest_layout_from_viewport_query_hint(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->get('/?sr_layout=mobile&sr_viewport_width=390')
            ->assertOk()
            ->assertSee('guest-shell guest-mobile-shell', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('css/mobile/style.css?v=30', false)
            ->assertSee('mobilePreviewSwiper', false)
            ->assertDontSee('css/desktop/style.css?v=7', false);
    }

    public function test_guest_landing_desktop_query_hint_can_override_stale_mobile_cookie(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->withUnencryptedCookie('sr_is_mobile', '1')
            ->withUnencryptedCookie('sr_viewport_width', '390')
            ->get('/?sr_layout=desktop&sr_viewport_width=1280')
            ->assertOk()
            ->assertSee('guest-shell guest-desktop-shell', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee('css/desktop/style.css?v=7', false)
            ->assertDontSee('css/mobile/style.css?v=30', false);
    }

    public function test_guest_landing_exposes_shell_marker_for_viewport_repair(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->get('/')
            ->assertOk()
            ->assertSee('guest-shell guest-desktop-shell', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee("body.getAttribute('data-layout-shell')", false)
            ->assertSee("bodyClasses.contains('guest-desktop-shell')", false)
            ->assertSee("url.searchParams.set('sr_layout', targetLayout)", false)
            ->assertSee('window.location.replace(url.toString())', false)
            ->assertSee('css/desktop/style.css?v=7', false);
    }

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
            ->assertSee('class="user-mobile-shell mobile-shell"', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('id="mob-content"', false)
            ->assertSee('--mob-card-gap: 12px', false)
            ->assertSee('--sr-visual-vh', false)
            ->assertSee('.tracker-panel', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_dashboard_uses_mobile_shell_for_mobile_user_agent(): void
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
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('id="mob-content"', false)
            ->assertSee('--sr-visual-vh', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertSee('class="admin-dashboard-title-text"', false)
            ->assertSee('admin-dashboard-shell', false)
            ->assertSee('dashboard-work-grid', false)
            ->assertSee('css/mobile/admin/dashboard.css?v=3', false)
            ->assertSee('id="mobFullscreenBtn"', false)
            ->assertSee('data-user-fullscreen-toggle', false)
            ->assertSee('js/user-ui.js?v=20', false)
            ->assertSee('body.admin-mobile-shell.user-app-fullscreen #mob-content', false)
            ->assertSee('grid-template-columns: repeat(5, minmax(0, 1fr));', false)
            ->assertSee('max-width: min(42vw, 12rem);', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_dashboard_keeps_desktop_shell_for_desktop_user_agent(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('class="admin-shell desktop-shell"', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee('class="admin-dashboard-title-text"', false)
            ->assertSee('admin-dashboard-shell', false)
            ->assertSee('dashboard-work-grid', false)
            ->assertSee('css/desktop/admin/dashboard.css?v=3', false)
            ->assertSee('class="db-sidebar"', false)
            ->assertDontSee('id="mob-content"', false);
    }

    public function test_admin_write_requests_are_accessible_from_mobile_user_agent(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->post(route('admin.settings.update'), [])
            ->assertRedirect();
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
            ->assertSee('user-desktop-shell desktop-shell', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee('class="db-sidebar"', false)
            ->assertDontSee('id="mob-bottom-nav"', false)
            ->assertDontSee('html body #progressModulesLikeHero.progress-hero', false);
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
            ->assertSee('class="user-mobile-shell mobile-shell"', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('id="mob-content"', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_user_mobile_bottom_nav_centers_interview_action(): void
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
            ->assertSee('class="mob-nav-primary-icon"', false)
            ->assertSee('aria-label="Start interview practice"', false)
            ->assertSee('<span>Interview</span>', false)
            ->assertSee('<span>More</span>', false)
            ->assertSee('class="fa-solid fa-chart-simple"', false)
            ->assertSee('class="fa-regular fa-clipboard-list"', false)
            ->assertSee('class="fa-solid fa-grid"', false);

        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/id="mobnav-home".*id="mobnav-progress".*id="mobnav-interview".*id="mobnav-feedback".*id="mobnav-more"/s',
            $content
        );

        $this->assertStringContainsString('class="mob-nav-item mob-nav-primary ', $content);
        $this->assertStringContainsString('--mob-nav-h: 112px;', $content);
        $this->assertStringContainsString('--mob-nav-overlap: 0px;', $content);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 78px minmax(0, 1fr) minmax(0, 1fr) !important;', $content);
        $this->assertStringContainsString('padding-bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 8px);', $content);
        $this->assertStringNotContainsString('padding-bottom: calc(var(--mob-nav-h) + var(--mob-nav-overlap) + var(--mob-safe-bottom) + 8px);', $content);
        $this->assertStringNotContainsString('height: calc(var(--mob-nav-h) + var(--mob-nav-overlap) + var(--mob-safe-bottom)) !important;', $content);
        $this->assertStringContainsString('--mob-dock-bg: transparent;', $content);
        $this->assertStringContainsString('--mob-dock-surface: #ffffff;', $content);
        $this->assertStringContainsString('--mob-dock-text: #64748b;', $content);
        $this->assertStringContainsString('--mob-dock-active: #076dff;', $content);
        $this->assertStringContainsString('.lm #mob-bottom-nav', $content);
        $this->assertStringContainsString('background: transparent !important;', $content);
        $this->assertStringContainsString('border-top: 0 !important;', $content);
        $this->assertStringContainsString('box-shadow: none !important;', $content);
        $this->assertStringContainsString('backdrop-filter: none !important;', $content);
        $this->assertStringContainsString('-webkit-backdrop-filter: none !important;', $content);
        $this->assertStringContainsString('left: max(10px, env(safe-area-inset-left, 0px)) !important;', $content);
        $this->assertStringContainsString('right: max(10px, env(safe-area-inset-right, 0px)) !important;', $content);
        $this->assertStringContainsString('bottom: calc(16px + var(--mob-safe-bottom)) !important;', $content);
        $this->assertStringContainsString('--mob-dock-height: 98px;', $content);
        $this->assertStringContainsString('--mob-dock-bar-top: 34px;', $content);
        $this->assertStringContainsString('--mob-dock-radius: 34px;', $content);
        $this->assertStringContainsString('--mob-dock-fab-size: 72px;', $content);
        $this->assertStringContainsString('height: var(--mob-dock-height) !important;', $content);
        $this->assertStringContainsString('max-width: 680px !important;', $content);
        $this->assertStringContainsString('background: transparent !important;', $content);
        $this->assertStringNotContainsString('linear-gradient(180deg, var(--bg) 0 var(--mob-nav-overlap), transparent var(--mob-nav-overlap)) !important;', $content);
        $this->assertStringNotContainsString('--mob-dock-cutout', $content);
        $this->assertStringNotContainsString('--mob-dock-fab-cutout', $content);
        $this->assertStringNotContainsString('.mob-nav-primary::before', $content);
        $this->assertStringContainsString('border-radius: 0 !important;', $content);
        $this->assertStringContainsString('top: 2px !important;', $content);
        $this->assertStringContainsString('width: calc(var(--mob-dock-fab-size) + 10px) !important;', $content);
        $this->assertStringContainsString('--mob-dock-fab-ring: rgba(255, 255, 255, 0.96);', $content);
        $this->assertStringContainsString('pointer-events: none !important;', $content);
        $this->assertStringNotContainsString('0 10px 22px rgba(37, 99, 235, 0.18)', $content);
        $this->assertStringNotContainsString('0 0 18px rgba(14, 165, 233, 0.14)', $content);
        $this->assertStringContainsString('top: var(--mob-dock-bar-top) !important;', $content);
        $this->assertStringContainsString('background: var(--mob-dock-surface) !important;', $content);
        $this->assertStringContainsString('border: 1px solid var(--mob-dock-border) !important;', $content);
        $this->assertStringContainsString('border-radius: var(--mob-dock-radius) !important;', $content);
        $this->assertStringContainsString('0 18px 20px rgba(14, 165, 233, 0.1)', $content);
        $this->assertStringNotContainsString('backdrop-filter: blur(18px) saturate(120%) !important;', $content);
        $this->assertStringNotContainsString('-webkit-backdrop-filter: blur(18px) saturate(120%) !important;', $content);
        $this->assertStringNotContainsString('box-shadow: 0 -14px 34px rgba(2, 6, 23, 0.3) !important;', $content);
        $this->assertStringNotContainsString('border-top: 1px solid var(--mob-dock-border) !important;', $content);
        $this->assertStringNotContainsString('calc(50% - 48px)', $content);
        $this->assertStringNotContainsString('calc(50% + 48px)', $content);
        $this->assertStringContainsString('padding: 0 14px 12px !important;', $content);
        $this->assertStringContainsString('pointer-events: auto !important;', $content);
        $this->assertStringContainsString('.mob-nav-item:not(.mob-nav-primary)', $content);
        $this->assertStringContainsString('.mob-nav-item.active {', $content);
        $this->assertStringNotContainsString('.mob-nav-item.active:not(.mob-nav-primary) {', $content);
        $this->assertStringContainsString('margin-top: 0 !important;', $content);
        $this->assertStringContainsString('justify-content: center !important;', $content);
        $this->assertStringContainsString('align-self: end !important;', $content);
        $this->assertStringContainsString('transform: translateY(0) !important;', $content);
        $this->assertStringContainsString('height: 98px !important;', $content);
        $this->assertStringContainsString('min-height: 98px !important;', $content);
        $this->assertStringContainsString('padding: 0 0 7px !important;', $content);
        $this->assertStringContainsString('.mob-nav-primary > span:last-child', $content);
        $this->assertStringContainsString('font-size: clamp(0.66rem, 2.45vw, 0.78rem) !important;', $content);
        $this->assertStringContainsString('font-weight: 900 !important;', $content);
        $this->assertStringContainsString('width: var(--mob-dock-fab-size) !important;', $content);
        $this->assertStringContainsString('height: var(--mob-dock-fab-size) !important;', $content);
        $this->assertStringContainsString('border: 6px solid var(--mob-dock-fab-ring) !important;', $content);
        $this->assertStringNotContainsString('border: 5px solid var(--mob-dock-bg) !important;', $content);
        $this->assertStringContainsString('0 0 0 1px rgba(147, 197, 253, 0.72)', $content);
        $this->assertStringNotContainsString('0 12px 20px rgba(37, 99, 235, 0.3)', $content);
        $this->assertStringNotContainsString('0 0 18px rgba(59, 130, 246, 0.24)', $content);
        $this->assertStringNotContainsString('0 12px 20px rgba(37, 99, 235, 0.34)', $content);
        $this->assertStringNotContainsString('0 0 22px rgba(59, 130, 246, 0.28)', $content);
        $this->assertStringContainsString('border-radius: 50% !important;', $content);
        $this->assertStringContainsString('font-size: clamp(1.42rem, 5.7vw, 1.92rem) !important;', $content);
        $this->assertStringContainsString('font-size: clamp(0.68rem, 2.45vw, 0.8rem) !important;', $content);
        $this->assertStringContainsString('text-decoration: none !important;', $content);
        $this->assertStringContainsString('text-shadow: none !important;', $content);
        $this->assertStringContainsString('position: relative !important;', $content);
        $this->assertStringNotContainsString('top: calc(var(--mob-nav-overlap) * -1) !important;', $content);
        $this->assertStringNotContainsString('translate: -50% 0 !important;', $content);
    }

    public function test_user_mobile_shell_includes_fullscreen_viewport_hardening(): void
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
            ->assertSee('id="mobFullscreenBtn"', false)
            ->assertSee('data-user-fullscreen-toggle', false)
            ->assertSee('body.mobile-shell.user-app-fullscreen #mob-content', false)
            ->assertSee('height: var(--sr-visual-vh) !important', false)
            ->assertSee('css/mobile/style.css?v=33', false)
            ->assertSee('js/main.js?v=7', false)
            ->assertSee('js/user-ui.js?v=20', false)
            ->assertSee('body.user-mobile-shell #mob-header #mobFullscreenBtn', false)
            ->assertSee('display: inline-flex !important;', false)
            ->assertDontSee('body.user-mobile-shell #mob-header #mobFullscreenBtn {
               display: none !important;', false);

        $fullscreenScript = file_get_contents(public_path('js/user-ui.js'));

        $this->assertStringContainsString('userApp.fullscreenFallbackActive', $fullscreenScript);
        $this->assertStringContainsString("root.requestFullscreen({ navigationUI: 'hide' })", $fullscreenScript);
        $this->assertStringContainsString("button.setAttribute('aria-pressed'", $fullscreenScript);
    }

    public function test_user_mobile_shell_does_not_render_quick_navigation_launcher(): void
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
            ->assertDontSee('class="ucp-mobile-launcher"', false)
            ->assertDontSee('data-ucp-context="user"', false)
            ->assertDontSee('id="userCommandPalette"', false)
            ->assertDontSee('id="userCommandList"', false)
            ->assertDontSee('id="ucp-destination-dashboard"', false)
            ->assertDontSee('id="ucp-destination-account"', false)
            ->assertDontSee('14 destinations')
            ->assertDontSee('data-ucp-search', false);
    }

    public function test_desktop_interview_session_hides_camera_detection_panel_when_enabled(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $category = Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'response_mode' => 'text',
            'accommodation_profile' => ['camera_detection' => true],
            'status' => 'in_progress',
        ]);
        Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a time you solved a difficult issue.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('interview.session'));

        $response->assertOk()
            ->assertSee('css/desktop/interview/session.css?v=42', false)
            ->assertSee('const cameraDetectionEnabled = true;', false)
            ->assertSee('interview-session-browser-fullscreen', false)
            ->assertSee('interview-ready-fullscreen', false)
            ->assertSee('Camera ON', false)
            ->assertDontSee('<i class="fa-regular fa-clock"></i>Self-paced', false)
            ->assertSee('function handleInterviewEscapeKey(event)', false)
            ->assertSee('exitReadyFullscreenShell();', false)
            ->assertSee('has-desktop-camera-pip', false)
            ->assertSee('class="desktop-camera-pip d-none d-lg-flex"', false)
            ->assertSee('id="userCamera"', false)
            ->assertSee('alt="AI Interviewer"', false)
            ->assertSee("document.getElementById('userCamera') || document.getElementById('userCameraMobile')", false)
            ->assertDontSee('id="questionTimerChip"', false)
            ->assertDontSee('id="cameraPanel"', false)
            ->assertDontSee('id="cameraDetectionStatus"', false);

        $desktopSessionCss = file_get_contents(public_path('css/desktop/interview/session.css'));
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen #sec-interview-session', $desktopSessionCss);
        $this->assertStringContainsString('height: calc(100dvh - clamp(32px, 4vw, 60px)) !important;', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen #workspaceRow > [class*="col-"]', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-session-two-column', $desktopSessionCss);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.80fr) !important;', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-interview-panel .ai-avatar-panel', $desktopSessionCss);
        $this->assertStringContainsString('width: clamp(320px, 21vw, 440px)', $desktopSessionCss);
        $this->assertStringContainsString('--spectrum-radius: clamp(178px, 11.6vw, 240px);', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-response-column .response-panel', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-response-column #answerTextarea', $desktopSessionCss);
        $this->assertStringContainsString('max-height: none !important;', $desktopSessionCss);
    }

    public function test_voice_and_hybrid_interview_sessions_use_inline_transcript_field_without_manual_transcript_button(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $category = Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';
        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        foreach (['voice', 'hybrid'] as $responseMode) {
            $session = InterviewSession::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => 'Developer',
                'num_questions' => 1,
                'response_mode' => $responseMode,
                'status' => 'in_progress',
            ]);
            Question::create([
                'category_id' => $category->id,
                'interview_session_id' => $session->id,
                'question_text' => "Describe a {$responseMode} response.",
                'difficulty' => 'medium',
                'type' => 'Behavioral',
                'status' => 'active',
            ]);

            foreach ([$desktopUserAgent, $iphoneUserAgent] as $userAgent) {
                $response = $this->actingAs($user)
                    ->withSession(['active_interview_id' => $session->id])
                    ->withHeader('User-Agent', $userAgent)
                    ->get(route('interview.session'));

                $response->assertOk()
                    ->assertSee('id="answerTextarea"', false)
                    ->assertSee('class="answer-transcript-stage"', false)
                    ->assertSee('id="responseCountBar"', false)
                    ->assertSee('id="answerTranscriptControls"', false)
                    ->assertSee('id="recordingTimer"', false)
                    ->assertSee('id="voiceControls"', false)
                    ->assertSee('Speak your answer, then edit the transcript here if needed...', false)
                    ->assertSee('const displayRealtimeTranscriptInTextarea = true;', false)
                    ->assertSee('function fullVoiceTranscriptionUnavailableMessage()', false)
                    ->assertSee('let activeTranscriptionEngine = isHybridTranscriptionMode() && displayRealtimeTranscriptInTextarea?', false)
                    ->assertSee('function renderSpeechTranscript()', false)
                    ->assertSee('function commitSpeechSegment(segment)', false)
                    ->assertSee('function currentRecordingTimerSeconds()', false)
                    ->assertSee('function scheduleRecordingTimerSideEffects(previousSeconds)', false)
                    ->assertSee('setInterval(() => syncRecordingTimerDisplay(), 250)', false)
                    ->assertSee('setTimeout(() => {', false)
                    ->assertSee('let voiceSessionRecordingStartedAt = 0;', false)
                    ->assertSee('voiceSessionRecordingStartedAt = recordingTimerNow();', false)
                    ->assertSee('startRecordingTimer(voiceSessionRecordingStartedAt);', false)
                    ->assertSee('pauseRecordingTimer();', false)
                    ->assertDontSee('recTimerSeconds++;', false)
                    ->assertSee('Recording ready - live transcript will appear in the answer box', false)
                    ->assertSee('Recording stopped - transcript is ready to edit', false)
                    ->assertSee('Full voice transcript added', false)
                    ->assertSee('await stopVoiceSessionRecorder();', false)
                    ->assertSee('async function transcribeVoiceSessionRecording', false)
                    ->assertSee('requestFullVoiceSessionTranscript(recording, question, previousTranscript)', false)
                    ->assertSee('replaceAnswerText: options.replaceAnswerText === true', false)
                    ->assertDontSee('async function flushCurrentRealtimeTranscriptForStop', false)
                    ->assertDontSee("const finalizedTranscript = await flushCurrentRealtimeTranscriptForStop('stop');", false)
                    ->assertDontSee("setTranscriptionStatus(finalizedTranscript? 'Transcript ready': '')", false)
                    ->assertDontSee('id="voiceSessionTranscript"', false)
                    ->assertDontSee('<span>Transcript</span>', false)
                    ->assertDontSee('updates every few seconds', false)
                    ->assertDontSee('Finalizing transcription', false)
                    ->assertSee('function autoCorrectTranscriptText', false)
                    ->assertSee('function appendVoiceSessionRecordingUpload', false)
                    ->assertSee("formData.append('voice_audio'", false)
                    ->assertSee('Voice answer saved for feedback. Transcript is unavailable, but playback will be available in your AI feedback review.', false)
                    ->assertSee('transcriptWordCorrections', false)
                    ->assertSee('/interview/transcribe', false)
                    ->assertDontSee('mobile-response-end-session-action', false)
                    ->assertDontSee('mobile-response-end-session-btn', false)
                    ->assertDontSee('id="voiceSessionDownload"', false);

                if ($responseMode === 'voice') {
                    $response->assertSee('id="voiceSessionPanel"', false)
                        ->assertSee('<span>Download</span>', false);
                } else {
                    $response->assertDontSee('id="voiceSessionPanel"', false)
                        ->assertDontSee('<span>Download</span>', false);
                }
            }
        }
    }

    public function test_mobile_interview_session_uses_camera_detection_gate_and_fullscreen_script(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $category = Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'response_mode' => 'text',
            'status' => 'in_progress',
        ]);
        Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a time you solved a difficult issue.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('interview.session'));

        $response->assertOk()
            ->assertSee('<body class="user-mobile-shell mobile-shell"', false)
            ->assertSee('css/mobile/interview/session.css?v=43', false)
            ->assertSee('const cameraDetectionEnabled = false;', false)
            ->assertSee('const cameraPreviewEnabled = cameraDetectionEnabled;', false)
            ->assertSee('Camera OFF', false)
            ->assertSee('function clearSubmittedAnswerInput()', false)
            ->assertSee("chatContainer.innerHTML = ''", false)
            ->assertSee('if (!isSubmittingAnswer)', false)
            ->assertDontSee('mobile-response-end-session-action', false)
            ->assertDontSee('mobile-response-end-session-btn', false)
            ->assertSee('id="responseFullscreenToggle"', false)
            ->assertSee('enterMobileFullscreen({ requestBrowser: false });', false)
            ->assertSee('enterMobileFullscreen();', false)
            ->assertSee('function handleInterviewEscapeKey(event)', false)
            ->assertSee('exitMobileFullscreen();', false)
            ->assertDontSee('class="mobile-camera-pip d-lg-none"', false)
            ->assertDontSee('id="userCameraMobile"', false)
            ->assertDontSee('<body class="mobile-interview-fullscreen"', false);

        $content = $response->getContent();
        $textareaPosition = strpos($content, 'id="answerTextarea"');
        $stageSearchContent = $textareaPosition === false? '': substr($content, 0, $textareaPosition);
        $stageOpenPosition = strrpos($stageSearchContent, 'class="answer-transcript-stage"');
        $counterPosition = strpos($content, 'id="responseCountBar"');
        $counterClosePosition = strpos($content, '</div>', $counterPosition);
        $stageClosePosition = strpos($content, '</div>', $counterClosePosition + 6);
        $controlsPosition = strpos($content, 'id="answerTranscriptControls"');
        $avatarPanelPosition = strpos($content, 'ai-avatar-panel');
        $aiQuestionTextPosition = strpos($content, 'id="aiQuestionText"');

        $this->assertNotFalse($stageOpenPosition);
        $this->assertNotFalse($textareaPosition);
        $this->assertNotFalse($counterPosition);
        $this->assertNotFalse($counterClosePosition);
        $this->assertNotFalse($stageClosePosition);
        $this->assertNotFalse($controlsPosition);
        $this->assertNotFalse($avatarPanelPosition);
        $this->assertNotFalse($aiQuestionTextPosition);
        $this->assertLessThan($textareaPosition, $stageOpenPosition);
        $this->assertLessThan($counterPosition, $textareaPosition);
        $this->assertLessThan($counterClosePosition, $counterPosition);
        $this->assertLessThan($stageClosePosition, $counterClosePosition);
        $this->assertLessThan($controlsPosition, $avatarPanelPosition);
        $this->assertLessThan($aiQuestionTextPosition, $controlsPosition);
        $this->assertLessThan($textareaPosition, $controlsPosition);

        $mobileSessionCss = file_get_contents(public_path('css/mobile/interview/session.css'));
        $this->assertStringContainsString('position: static;', $mobileSessionCss);
        $this->assertStringContainsString('padding: 12px 12px 36px !important;', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(148px, 32dvh, 190px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(174px, calc(var(--sr-visual-vh, 100dvh) - 422px), 238px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(162px, calc(var(--sr-visual-vh, 100dvh) - 404px), 224px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('margin-bottom: 14px !important;', $mobileSessionCss);
        $this->assertStringContainsString('overflow: hidden !important;', $mobileSessionCss);
        $this->assertStringContainsString('html body.mobile-interview-fullscreen.user-mobile-shell #sec-interview-session .response-panel', $mobileSessionCss);
        $this->assertStringNotContainsString('height: clamp(292px, 46dvh, 340px) !important;', $mobileSessionCss);
        $this->assertStringNotContainsString('height: clamp(270px, 44dvh, 318px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('padding: 10px 10px 30px !important;', $mobileSessionCss);
        $this->assertStringContainsString('html body.user-mobile-shell #sec-interview-session .response-autosave-row', $mobileSessionCss);
        $this->assertStringContainsString('inset: auto 12px 10px auto !important;', $mobileSessionCss);
        $this->assertStringContainsString('right: 12px !important;', $mobileSessionCss);
        $this->assertStringContainsString('left: auto !important;', $mobileSessionCss);
        $this->assertStringContainsString('background: transparent !important;', $mobileSessionCss);
        $this->assertStringContainsString('box-shadow: none !important;', $mobileSessionCss);
        $this->assertStringContainsString('overflow-wrap: anywhere !important;', $mobileSessionCss);
        $this->assertStringContainsString('text-overflow: clip !important;', $mobileSessionCss);
        $this->assertStringContainsString('white-space: normal !important;', $mobileSessionCss);
        $this->assertStringContainsString('--interview-avatar-lift: clamp(-34px, -4vh, -22px);', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(390px, calc(var(--sr-visual-vh, 100dvh) * 0.55), 520px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('width: clamp(152px, 42vw, 180px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('--spectrum-radius: clamp(88px, 23vw, 96px);', $mobileSessionCss);
        $this->assertStringContainsString('transform: translateY(var(--interview-avatar-lift)) !important;', $mobileSessionCss);
        $this->assertStringContainsString('inset: auto 16px 22px 16px !important;', $mobileSessionCss);

        $session->update([
            'accommodation_profile' => ['camera_detection' => true],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('interview.session'));

        $response->assertOk()
            ->assertSee('const cameraDetectionEnabled = true;', false)
            ->assertSee('Camera ON', false)
            ->assertSee('class="mobile-camera-pip d-lg-none"', false)
            ->assertSee('id="userCameraMobile"', false)
            ->assertDontSee('id="cameraPanel"', false)
            ->assertDontSee('id="cameraDetectionStatus"', false);
    }
}
