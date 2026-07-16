<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ExperienceStory;
use App\Models\GameLevel;
use App\Models\InterviewOutcome;
use App\Models\InterviewPack;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\ReadinessProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminNewFeatureConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_interview_pack_is_available_and_applied_on_user_side(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['type' => 'core']);

        $this->actingAs($admin)
            ->post(route('admin.packs.store'), [
                'name' => 'Healthcare Support Panel',
                'slug' => 'healthcare-support-panel',
                'company' => 'North Clinic',
                'role_family' => 'Healthcare',
                'difficulty' => 'hard',
                'interview_focus' => 'Empathy',
                'company_persona' => 'Clinical Hiring Panel',
                'question_types_text' => "Behavioral\nSituational",
                'sample_questions_text' => "Tell me about a time you calmed an upset patient.\nHow do you prioritize urgent service requests?",
                'description' => 'Healthcare support interview practice.',
                'pressure_mode' => '1',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.packs.index'));

        $pack = InterviewPack::where('slug', 'healthcare-support-panel')->firstOrFail();

        $this->actingAs($user)
            ->get(route('user.packs.index'))
            ->assertOk()
            ->assertSee('Healthcare Support Panel')
            ->assertSee('North Clinic');

        $this->actingAs($user)
            ->post(route('interview.start'), [
                'category_id' => $category->id,
                'interview_pack_id' => $pack->id,
                'target_position' => 'Healthcare Support Associate',
                'difficulty' => 'medium',
                'num_questions' => 5,
                'response_mode' => 'text',
                'interview_focus' => 'General Practice',
            ])
            ->assertRedirect(route('interview.session'));

        $this->assertDatabaseHas('interview_sessions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'interview_pack_id' => $pack->id,
            'difficulty' => 'hard',
            'pressure_mode' => true,
            'company_persona' => 'Clinical Hiring Panel',
        ]);

        $this->assertDatabaseHas('questions', [
            'category_id' => $category->id,
            'question_text' => 'Tell me about a time you calmed an upset patient.',
        ]);
    }

    public function test_add_interview_pack_modal_reopens_with_scoped_validation_errors(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('admin.packs.index'))
            ->post(route('admin.packs.store'), [
                '_pack_modal_id' => 'addPackModal',
                'name' => '',
                'status' => 'active',
                'difficulty' => 'medium',
                'interview_focus' => '',
                'question_types_text' => 'Behavioral',
            ])
            ->assertOk()
            ->assertSee('Please fix the highlighted fields')
            ->assertSee('Behavioral')
            ->assertSee('getElementById("addPackModal")', false);
    }

    public function test_interview_pack_modal_uses_viewport_bound_scrollable_layout(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('admin.packs.index'));
        $html = $response->baseResponse->getContent();

        $response
            ->assertOk()
            ->assertSee('pack-form-modal', false)
            ->assertSee('modal-dialog modal-lg modal-dialog-scrollable', false)
            ->assertSee('body.admin-shell .modal.pack-form-modal', false)
            ->assertSee('height: calc(100dvh - 32px) !important', false)
            ->assertSee('overflow-y: auto', false);

        $this->assertStringNotContainsString('#sec-admin-packs .pack-form-modal', $html);
        $this->assertGreaterThan(
            strpos($html, 'id="sec-admin-packs"'),
            strpos($html, 'id="addPackModal"')
        );
    }

    public function test_interview_pack_modal_has_mobile_safe_area_overrides(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $response = $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.packs.index'));
        $html = $response->baseResponse->getContent();

        $response
            ->assertOk()
            ->assertSee('id="mob-content"', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertSee('body.admin-mobile-shell .modal.pack-form-modal.show', false)
            ->assertSee('calc(100dvh - var(--mob-nav-h, 64px)', false)
            ->assertSee('textarea.form-control', false);

        $this->assertGreaterThan(
            strpos($html, 'body.admin-mobile-shell .modal .modal-dialog.modal-dialog-scrollable'),
            strpos($html, 'body.admin-mobile-shell .modal.pack-form-modal.show')
        );
    }

    public function test_interview_pack_mobile_ui_uses_cards_and_compact_switch(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        InterviewPack::create([
            'name' => 'Mobile Support Pack',
            'slug' => 'mobile-support-pack',
            'company' => 'Mobile Co',
            'role_family' => 'Support',
            'difficulty' => 'medium',
            'interview_focus' => 'Communication',
            'question_types' => ['Behavioral', 'Situational'],
            'sample_questions' => ['Tell me about a time you helped a customer.'],
            'status' => 'active',
            'pressure_mode' => false,
        ]);
        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.packs.index'))
            ->assertOk()
            ->assertSee('pack-table-wrap', false)
            ->assertSee('pack-mobile-list', false)
            ->assertSee('pack-mobile-card', false)
            ->assertSee('Mobile Support Pack')
            ->assertSee('Mobile Co')
            ->assertSee('pack-pressure-check', false)
            ->assertSee('body.admin-mobile-shell .modal.pack-form-modal .pack-pressure-input', false)
            ->assertSee('width: 18px !important', false)
            ->assertSee('background-color: #0ea5e9 !important', false)
            ->assertSee('for="addPackModal_name"', false)
            ->assertSee('id="addPackModal_pressure_mode"', false);
    }

    public function test_admin_readiness_dashboard_surfaces_new_career_features(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active', 'name' => 'Career Candidate']);
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Northstar Labs',
            'job_title' => 'Software Engineer',
            'status' => 'interviewing',
            'match_score' => 82,
            'evidence_match_score' => 76,
            'resume_text' => 'Built reliable APIs.',
            'job_description' => 'Build software and improve reliability.',
        ]);

        ReadinessProfile::create([
            'user_id' => $user->id,
            'job_application_id' => $application->id,
            'target_role' => 'Software Engineer',
            'competency_map' => ['Reliability'],
            'calibrated_at' => now(),
        ]);

        ExperienceStory::create([
            'user_id' => $user->id,
            'title' => 'Reliability project',
            'context_type' => 'work',
            'action' => 'Implemented caching and load tests.',
            'facts_confirmed' => true,
            'visibility' => 'private',
        ]);

        InterviewOutcome::create([
            'user_id' => $user->id,
            'job_application_id' => $application->id,
            'interview_date' => now()->toDateString(),
            'interview_format' => 'panel',
            'result' => 'advanced',
            'stage' => 'Technical panel',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.readiness.index'))
            ->assertOk()
            ->assertSee('Readiness Careers')
            ->assertSee('Career Candidate')
            ->assertSee('Northstar Labs')
            ->assertSee('Reliability project')
            ->assertSee('Technical panel');
    }

    public function test_admin_readiness_dashboard_falls_back_when_evidence_score_column_is_missing(): void
    {
        Schema::table('job_applications', function ($table) {
            $table->dropColumn('evidence_match_score');
        });

        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active', 'name' => 'Legacy Schema User']);

        JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Legacy Co',
            'job_title' => 'Support Associate',
            'status' => 'tracking',
            'match_score' => 64,
            'resume_text' => 'Supported customers and documented fixes.',
            'job_description' => 'Support customers and communicate clearly.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.readiness.index'))
            ->assertOk()
            ->assertSee('Legacy Schema User')
            ->assertSee('Legacy Co')
            ->assertSee('64%');
    }

    public function test_admin_can_support_manage_user_readiness_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Acme Health',
            'job_title' => 'Support Analyst',
            'status' => 'tracking',
            'resume_text' => 'Handled support tickets and improved documentation.',
            'job_description' => 'Support users, communicate clearly, and improve service quality.',
        ]);
        $story = ExperienceStory::create([
            'user_id' => $user->id,
            'title' => 'Documentation improvement',
            'context_type' => 'work',
            'action' => 'I rewrote the onboarding documentation.',
            'result' => 'Reduced repeat questions.',
            'facts_confirmed' => false,
            'visibility' => 'private',
        ]);
        $outcome = InterviewOutcome::create([
            'user_id' => $user->id,
            'job_application_id' => $application->id,
            'interview_date' => now()->toDateString(),
            'interview_format' => 'video',
            'result' => 'pending',
            'stage' => 'HR screen',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.readiness.applications.update', $application), [
                'status' => 'interviewing',
                'interview_stage' => 'Technical panel',
                'interview_date' => now()->addWeek()->toDateString(),
                'notes' => 'Needs panel prep support.',
            ])
            ->assertRedirect(route('admin.readiness.index'));

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => 'interviewing',
            'interview_stage' => 'Technical panel',
            'notes' => 'Needs panel prep support.',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.readiness.stories.update', $story), [
                'facts_confirmed' => '1',
                'visibility' => 'support_review',
            ])
            ->assertRedirect(route('admin.readiness.index'));

        $this->assertDatabaseHas('experience_stories', [
            'id' => $story->id,
            'facts_confirmed' => true,
            'visibility' => 'support_review',
        ]);
        $this->assertDatabaseHas('readiness_profiles', [
            'user_id' => $user->id,
            'job_application_id' => $application->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.readiness.outcomes.update', $outcome), [
                'result' => 'offer',
                'stage' => 'Final interview',
                'allow_anonymous_learning' => '1',
            ])
            ->assertRedirect(route('admin.readiness.index'));

        $this->assertDatabaseHas('interview_outcomes', [
            'id' => $outcome->id,
            'result' => 'offer',
            'stage' => 'Final interview',
            'allow_anonymous_learning' => true,
        ]);
        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => 'offer',
        ]);
    }

    public function test_hidden_learning_games_do_not_render_on_user_side(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => 3]);
        $category = $this->category(['type' => 'game']);

        $this->gameLevel($category, ['title' => 'Visible Confidence Sprint', 'level_number' => 1, 'is_hidden' => false]);
        $this->gameLevel($category, ['title' => 'Hidden Draft Challenge', 'level_number' => 2, 'is_hidden' => true]);

        $this->actingAs($user)
            ->get(route('user.learning', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('Visible Confidence Sprint')
            ->assertDontSee('Hidden Draft Challenge');
    }

    public function test_admin_settings_toggles_are_enforced_on_user_features(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id]);

        Setting::setVal('aic_enable', false, 'general', 'boolean');
        $this->actingAs($user)
            ->get(route('user.coach'))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($user)
            ->postJson(route('user.coach.chat'), ['message' => 'Help me prepare.', 'history' => []])
            ->assertStatus(403)
            ->assertJsonPath('error', 'coach_disabled');

        Setting::setVal('vr_recording', false, 'general', 'boolean');
        $this->actingAs($user)
            ->get(route('user.drills.voice'))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($user)
            ->postJson(route('user.drills.voice.save'), ['category' => 'Behavioral'])
            ->assertStatus(403);

        Setting::setVal('ll_modules', false, 'general', 'boolean');
        $this->actingAs($user)
            ->get(route('user.modules.index'))
            ->assertRedirect(route('dashboard'));

        Setting::setVal('acc_registration', false, 'general', 'boolean');
        $this->post(route('register'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Behavioral '.uniqid(),
            'description' => 'Practice category',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function gameLevel(Category $category, array $overrides = []): GameLevel
    {
        return GameLevel::create(array_merge([
            'category_id' => $category->id,
            'level_number' => 1,
            'title' => 'Practice Challenge',
            'description' => 'Practice clear responses.',
            'mission_text' => '1. Introduce yourself clearly.',
            'target_position' => 'Better Communication',
            'difficulty' => 'beginner',
            'required_score' => 60,
            'xp_reward' => 100,
            'energy_cost' => 1,
            'is_hidden' => false,
        ], $overrides));
    }
}
