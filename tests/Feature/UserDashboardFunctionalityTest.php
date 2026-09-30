<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\Profile;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_hides_admin_algorithm_checks_when_score_history_exists(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $peer = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        Profile::create(['user_id' => $user->id, 'readiness_score' => 81]);
        LearningModule::create([
            'title' => 'Grammar Fluency Practice',
            'description' => 'Practice grammar sentence control language fluency and clear word choice.',
            'type' => 'article',
            'category' => 'Interview Skills',
            'difficulty' => 'medium',
            'status' => 'published',
            'mapped_skills' => ['grammar', 'fluency'],
        ]);

        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 84,
            'relevance_score' => 86,
            'grammar_score' => 80,
            'professionalism_score' => 82,
            'overall_readiness_score' => 86,
            'readiness_band' => 'Ready for Simulation',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 80,
            'relevance_score' => 83,
            'grammar_score' => 79,
            'professionalism_score' => 78,
            'overall_readiness_score' => 83,
            'readiness_band' => 'Ready for Simulation',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 62,
            'relevance_score' => 61,
            'grammar_score' => 70,
            'professionalism_score' => 66,
            'overall_readiness_score' => 64,
            'readiness_band' => 'Nearly Ready',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 85,
            'relevance_score' => 85,
            'grammar_score' => 79,
            'professionalism_score' => 81,
            'overall_readiness_score' => 84,
            'readiness_band' => 'Ready for Simulation',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 35,
            'relevance_score' => 40,
            'grammar_score' => 45,
            'professionalism_score' => 42,
            'overall_readiness_score' => 40,
            'readiness_band' => 'Developing',
        ]);
        $this->completedSessionWithScore($user, $category, [
            'clarity_score' => 82,
            'relevance_score' => 84,
            'grammar_score' => 78,
            'professionalism_score' => 80,
            'overall_readiness_score' => 81,
            'readiness_band' => 'Ready for Simulation',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('KNN Readiness Match')
            ->assertDontSee('Algorithm Checks')
            ->assertDontSee('Decision Tree')
            ->assertDontSee('TF-IDF Cosine Similarity')
            ->assertViewMissing('knnReadiness')
            ->assertViewMissing('readinessAlgorithms');
    }

    public function test_dashboard_renders_setup_tools_modal_for_desktop_and_mobile_shells(): void
    {
        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
        $cases = [
            [
                'user' => User::factory()->create(['is_admin' => false, 'status' => 'active']),
                'headers' => [],
                'shell' => 'class="user-desktop-shell desktop-shell',
                'css' => 'css/desktop/dashboard.css?v=50',
            ],
            [
                'user' => User::factory()->create(['is_admin' => false, 'status' => 'active']),
                'headers' => ['User-Agent' => $iphoneUserAgent],
                'shell' => 'class="user-mobile-shell mobile-shell',
                'css' => 'css/mobile/dashboard.css?v=42',
            ],
        ];

        foreach ($cases as $case) {
            $request = $this->actingAs($case['user']);

            foreach ($case['headers'] as $header => $value) {
                $request = $request->withHeader($header, $value);
            }

            $response = $request->get(route('dashboard'));

            $response
                ->assertOk()
                ->assertSee($case['shell'], false)
                ->assertSee($case['css'], false)
                ->assertSee('img/dashboard-hero-robot-reference.png', false)
                ->assertDontSee('localStorage.setItem(storageKey', false)
                ->assertDontSee('localStorage.getItem(storageKey', false)
                ->assertDontSee('Click the robot for AI Coach.')
                ->assertSee('id="dashboardSetupToolsModal"', false)
                ->assertSee('id="dashboardSetupToolsForm"', false)
                ->assertSee('data-sr-setup-tool="microphone"', false)
                ->assertSee('data-sr-setup-tool="camera"', false)
                ->assertSee('data-sr-setup-tool="notifications"', false)
                ->assertSee('role="switch"', false)
                ->assertSee('Browser permission check')
                ->assertSee('id="dashboardSetupToolsAllow"', false)
                ->assertSee('Do later')
                ->assertSee('navigator.mediaDevices.getUserMedia', false)
                ->assertSee('Notification.requestPermission', false)
                ->assertSee('Some selected tools still need browser permission.', false);

            if (str_contains($case['css'], 'mobile/dashboard.css')) {
                $response
                    ->assertSee('id="dashboardCoachImageTrigger"', false)
                    ->assertSee('sr-image-coach-trigger', false)
                    ->assertSee('Tap the robot for AI Coach.')
                    ->assertSee('data-bs-target="#dashboardCoachModal"', false)
                    ->assertSee('aria-controls="dashboardCoachModal"', false)
                    ->assertDontSee('id="dashboardCoachFloatingLauncher"', false)
                    ->assertDontSee('data-dashboard-coach-launcher', false)
                    ->assertDontSee('data-dashboard-coach-fixed', false)
                    ->assertDontSee('data-dashboard-coach-draggable', false)
                    ->assertDontSee('img/dashboard-coach-floating-robot.png', false)
                    ->assertDontSee('Open AI Coach from More when you need help.')
                    ->assertDontSee('Use the floating coach icon for AI Coach.')
                    ->assertDontSee('document.body.appendChild(launcher)', false)
                    ->assertDontSee("localStorage.removeItem('speakready.dashboardCoachLauncher.mobile')", false);
            } else {
                $response
                    ->assertDontSee('id="dashboardCoachImageTrigger"', false)
                    ->assertDontSee('sr-image-coach-trigger', false)
                    ->assertSee('id="dashboardCoachFloatingLauncher"', false)
                    ->assertSee('data-dashboard-coach-launcher', false)
                    ->assertSee('img/dashboard-coach-floating-robot.png', false)
                    ->assertSee('Use the floating coach icon for AI Coach.')
                    ->assertSee('data-dashboard-coach-draggable', false)
                    ->assertSee('initDashboardCoachLauncherDrag', false)
                    ->assertSee("localStorage.removeItem('speakready.dashboardCoachLauncher.desktop')", false);
            }
        }
    }

    public function test_desktop_dashboard_does_not_show_practice_mode_status_pill(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'readiness_score' => 25]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="user-desktop-shell desktop-shell', false)
            ->assertDontSee('sr-status-pill', false)
            ->assertDontSee('Practice Mode');
    }

    public function test_removed_application_and_pack_urls_are_not_available(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        foreach (['/applications', '/applications/1/practice', '/practice-plan/1/toggle', '/packs', '/packs/1/practice'] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertNotFound();
        }
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Job Interview',
            'description' => 'General interview practice',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function completedSessionWithScore(User $user, Category $category, array $scoreAttributes): InterviewSession
    {
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'status' => 'completed',
            'assessment_mode' => 'legacy',
            'score_eligible' => true,
        ]);

        Score::create(array_merge([
            'interview_session_id' => $session->id,
            'score_version' => 5,
            'assessment_mode' => 'legacy',
            'clarity_score' => 0,
            'relevance_score' => 0,
            'grammar_score' => 0,
            'professionalism_score' => 0,
            'confidence_score' => 0,
            'delivery_stability_score' => 0,
            'star_method_score' => 0,
            'overall_readiness_score' => 0,
            'readiness_band' => 'Developing',
        ], $scoreAttributes));

        return $session;
    }

    private function interviewPayload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 5,
            'response_mode' => 'text',
            'time_limit' => 0,
            'ai_provider' => 'local',
        ];
    }
}
