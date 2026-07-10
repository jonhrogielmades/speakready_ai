<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameLevel;
use App\Models\InterviewAnswer;
use App\Models\InterviewPack;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;
use App\Models\Profile;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSideHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_relation_powers_learning_state(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $profile = Profile::create([
            'user_id' => $user->id,
            'energy' => 2,
            'player_level' => 4,
        ]);
        $category = $this->category(['type' => 'game']);
        $this->gameLevel($category);

        $this->assertTrue($profile->is($user->fresh()->profile));

        $this->actingAs($user)
            ->get(route('user.learning', ['category_id' => $category->id]))
            ->assertOk();
    }

    public function test_user_can_choose_language_from_profile_menu(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="profileLanguageSelect"', false)
            ->assertSee('<option value="tl"', false)
            ->assertSee('<option value="ceb"', false);

        $this->actingAs($user)
            ->post(route('user.language.update'), [
                'preferred_language' => 'ceb',
            ])
            ->assertRedirect();

        $this->assertSame('ceb', $user->fresh()->preferred_language);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lang="ceb"', false)
            ->assertSee('data-speech-locale="ceb-PH"', false);
    }

    public function test_english_translation_endpoint_returns_identity_without_ai_provider(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active', 'preferred_language' => 'en']);

        $this->actingAs($user)
            ->postJson(route('user.language.translate'), [
                'texts' => ['Account Management', 'Notifications'],
            ])
            ->assertOk()
            ->assertJsonPath('language', 'en')
            ->assertJsonPath('translations.Account Management', 'Account Management')
            ->assertJsonPath('translations.Notifications', 'Notifications');
    }

    public function test_perk_unlock_uses_server_catalog_cost_and_type(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create([
            'user_id' => $user->id,
            'leadership_xp' => 500,
            'technical_xp' => 999,
        ]);

        $this->actingAs($user)
            ->postJson(route('user.skills.unlock'), [
                'perk_id' => 'energy_efficiency',
                'perk_type' => 'technical',
                'cost' => 0,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $profile = Profile::where('user_id', $user->id)->first();

        $this->assertSame(0, $profile->leadership_xp);
        $this->assertSame(999, $profile->technical_xp);
        $this->assertTrue($profile->hasPerk('energy_efficiency'));
    }

    public function test_perk_unlock_rejects_unknown_perks(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'leadership_xp' => 9999]);

        $this->actingAs($user)
            ->postJson(route('user.skills.unlock'), ['perk_id' => 'free_everything'])
            ->assertUnprocessable();

        $this->assertFalse(Profile::where('user_id', $user->id)->first()->hasPerk('free_everything'));
    }

    public function test_interview_start_requires_active_core_category(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $gameCategory = $this->category(['type' => 'game']);
        $inactiveCategory = $this->category(['title' => 'Inactive Core', 'status' => 'inactive']);

        foreach ([$gameCategory, $inactiveCategory] as $category) {
            $this->actingAs($user)
                ->from(route('interview.setup'))
                ->post(route('interview.start'), $this->interviewPayload($category))
                ->assertRedirect(route('interview.setup'))
                ->assertSessionHasErrors('category_id');
        }

        $this->assertDatabaseCount('interview_sessions', 0);
    }

    public function test_interview_start_accepts_active_core_category(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $this->actingAs($user)
            ->post(route('interview.start'), $this->interviewPayload($category))
            ->assertRedirect(route('interview.session'));

        $this->assertDatabaseHas('interview_sessions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'status' => 'in_progress',
        ]);
    }

    public function test_application_tracker_generates_match_report_and_practice_plan(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('user.applications.store'), [
                'company_name' => 'Acme AI',
                'job_title' => 'Laravel Developer',
                'status' => 'applied',
                'interview_stage' => 'Technical screen',
                'resume_text' => 'Laravel API testing MySQL communication leadership',
                'job_description' => 'Laravel API testing MySQL communication leadership Docker queues',
            ])
            ->assertRedirect(route('user.applications.index'));

        $application = JobApplication::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Acme AI', $application->company_name);
        $this->assertGreaterThan(0, $application->match_score);
        $this->assertNotEmpty($application->smart_plan);
        $this->assertGreaterThanOrEqual(
            7,
            PracticePlanItem::where('job_application_id', $application->id)->count()
        );
    }

    public function test_interview_start_applies_application_pack_and_pressure_mode(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Acme AI',
            'job_title' => 'Platform Engineer',
            'resume_text' => 'Laravel systems testing',
            'job_description' => 'Own Laravel APIs and system reliability',
        ]);
        $pack = InterviewPack::create([
            'name' => 'Acme Pressure Pack',
            'slug' => 'acme-pressure-pack',
            'company' => 'Acme AI',
            'role_family' => 'Engineering',
            'difficulty' => 'hard',
            'interview_focus' => 'Problem Solving',
            'company_persona' => 'Technical Panel',
            'question_types' => ['Technical', 'Situational'],
            'sample_questions' => ['Walk me through a production incident.'],
            'pressure_mode' => true,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
                'job_application_id' => $application->id,
                'interview_pack_id' => $pack->id,
            ]))
            ->assertRedirect(route('interview.session'));

        $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($application->id, $session->job_application_id);
        $this->assertSame($pack->id, $session->interview_pack_id);
        $this->assertTrue($session->pressure_mode);
        $this->assertSame('hard', $session->difficulty);
        $this->assertSame('Problem Solving', $session->interview_focus);
        $this->assertSame('Technical Panel', $session->company_persona);
        $this->assertSame('strict', $session->interviewer_strictness);
        $this->assertSame('challenge', $session->ai_assistance_level);
        $this->assertSame('real_interview', $session->live_feedback_mode);
        $this->assertSame(2, $session->time_limit);
        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'question_text' => 'Walk me through a production incident.',
        ]);
    }

    public function test_public_shared_review_accepts_mentor_comment(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $session->update([
            'is_public' => true,
            'share_token' => 'public-session-token',
        ]);

        $this->post(route('shared.mentor-comments.store', $session->share_token), [
            'reviewer_name' => 'Mentor One',
            'reviewer_email' => 'mentor@example.com',
            'rating' => 5,
            'comment' => 'Strong structure and clear examples. Keep tightening the measurable results.',
        ])
            ->assertRedirect(route('shared.review', $session->share_token));

        $this->assertDatabaseHas('mentor_review_comments', [
            'interview_session_id' => $session->id,
            'reviewer_name' => 'Mentor One',
            'rating' => 5,
        ]);
    }

    public function test_new_career_pages_render_for_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->category();
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Acme AI',
            'job_title' => 'Backend Developer',
            'resume_text' => 'Laravel API testing',
            'job_description' => 'Laravel API testing queues',
        ]);
        $pack = InterviewPack::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('user.applications.index'))
            ->assertOk()
            ->assertSee('Job Application Tracker')
            ->assertSee('id="job-tracker-summary"', false)
            ->assertSee('id="job-tracker-form"', false);

        $this->actingAs($user)
            ->get(route('user.packs.index'))
            ->assertOk()
            ->assertSee('Interview Packs')
            ->assertSee('id="pack-summary"', false)
            ->assertSee('id="pack-browser"', false);

        $this->actingAs($user)
            ->get(route('interview.setup', [
                'application' => $application->id,
                'pack' => $pack->id,
            ]))
            ->assertOk()
            ->assertSee('Pressure Mode');
    }

    public function test_practice_plan_toggle_is_scoped_to_owner(): void
    {
        $owner = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $application = JobApplication::create([
            'user_id' => $owner->id,
            'company_name' => 'Acme AI',
            'job_title' => 'Backend Developer',
            'resume_text' => 'Laravel API testing',
            'job_description' => 'Laravel API testing queues',
        ]);
        $item = PracticePlanItem::create([
            'user_id' => $owner->id,
            'job_application_id' => $application->id,
            'day_number' => 1,
            'due_date' => now()->toDateString(),
            'type' => 'match',
            'title' => 'Tighten Job Match',
            'task' => 'Connect one answer to the job description.',
        ]);

        $this->actingAs($otherUser)
            ->postJson(route('user.practice-plan.toggle', $item))
            ->assertForbidden();

        $this->assertNull($item->fresh()->completed_at);

        $this->actingAs($owner)
            ->postJson(route('user.practice-plan.toggle', $item))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'completed' => true,
            ]);

        $this->assertNotNull($item->fresh()->completed_at);
    }

    public function test_voice_session_save_recomputes_measurable_metrics(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->postJson(route('user.drills.voice.save'), [
                'category' => 'Behavioral',
                'prompt' => 'Tell me about a project.',
                'transcript' => 'Um I solved the issue clearly',
                'duration_seconds' => 60,
                'wpm' => 400,
                'filler_words' => 200,
                'clarity_score' => 100,
                'confidence_score' => 100,
                'speaking_pace' => 400,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('voice_sessions', [
            'user_id' => $user->id,
            'wpm' => 6,
            'speaking_pace' => 6,
            'filler_words' => 1,
            'clarity_score' => 58,
            'confidence_score' => 60,
        ]);
    }

    public function test_interview_answer_recomputes_confidence_from_delivery_metrics(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'question_id' => $question->id,
                'answer_text' => 'I did it.',
                'response_mode' => 'voice',
                'voice_duration' => 30,
                'wpm' => 100,
                'filler_words_count' => 0,
                'pause_count' => 0,
                'confidence_score' => 100,
                'eye_contact_score' => 90,
                'posture_score' => 90,
            ])
            ->assertOk();

        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'confidence_score' => 62,
        ]);
    }

    public function test_interview_answer_rejects_out_of_range_delivery_metrics(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'question_id' => $question->id,
                'answer_text' => 'This should not be accepted.',
                'wpm' => 999,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_interview_answer_cleans_adjacent_transcript_duplicates(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'question_id' => $question->id,
                'answer_text' => 'I led a migration I led a migration and reduced downtime downtime.',
                'response_mode' => 'voice',
            ])
            ->assertOk();

        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I led a migration and reduced downtime',
        ]);
    }

    public function test_game_start_stops_when_energy_is_empty_after_today_refill(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create([
            'user_id' => $user->id,
            'energy' => 0,
            'energy_last_refilled_at' => now(),
        ]);
        $category = $this->category(['type' => 'game']);
        $level = $this->gameLevel($category);

        $this->actingAs($user)
            ->from(route('user.learning', ['category_id' => $category->id]))
            ->post(route('user.game.start', $level))
            ->assertRedirect(route('user.learning', ['category_id' => $category->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('interview_sessions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $this->assertSame(0, Profile::where('user_id', $user->id)->first()->energy);
    }

    public function test_game_start_refills_daily_energy_and_consumes_cost(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create([
            'user_id' => $user->id,
            'energy' => 0,
            'energy_last_refilled_at' => now()->subDay(),
        ]);
        $category = $this->category(['type' => 'game']);
        $level = $this->gameLevel($category);

        $this->actingAs($user)
            ->post(route('user.game.start', $level))
            ->assertRedirect(route('user.game.match'));

        $this->assertSame(2, Profile::where('user_id', $user->id)->first()->energy);
        $this->assertDatabaseHas('interview_sessions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_inactive_user_session_is_logged_out_by_user_middleware(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'inactive']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function question(Category $category, array $overrides = []): Question
    {
        return Question::create(array_merge([
            'category_id' => $category->id,
            'question_text' => 'Describe a difficult project.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ], $overrides));
    }

    private function sessionFor(User $user, Category $category): InterviewSession
    {
        return InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'in_progress',
        ]);
    }

    private function gameLevel(Category $category, array $overrides = []): GameLevel
    {
        return GameLevel::create(array_merge([
            'category_id' => $category->id,
            'level_number' => 1,
            'title' => 'Opening Challenge',
            'description' => 'Practice a concise response.',
            'mission_text' => 'Tell me about yourself.',
            'target_position' => 'Developer',
            'difficulty' => 'beginner',
            'required_score' => 80,
            'xp_reward' => 100,
            'energy_cost' => 1,
            'is_hidden' => false,
        ], $overrides));
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
