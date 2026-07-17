<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAnswer;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\GameSession;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    public function test_language_update_falls_back_to_session_when_column_is_missing(): void
    {
        Schema::table('users', function ($table) {
            $table->dropColumn('preferred_language');
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('user.language.update'), [
                'preferred_language' => 'fil',
            ])
            ->assertRedirect()
            ->assertSessionHas('preferred_language', 'fil');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lang="fil"', false)
            ->assertSee('data-speech-locale="fil-PH"', false);
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
        $gameCategory = $this->category(['title' => 'Game Category', 'type' => 'game']);
        $level = $this->gameLevel($gameCategory);

        $this->actingAs($user)
            ->withSession([
                'game_level_id' => $level->id,
                'active_interview_context' => 'learning_game',
            ])
            ->post(route('interview.start'), $this->interviewPayload($category))
            ->assertRedirect(route('interview.session'))
            ->assertSessionMissing('game_level_id')
            ->assertSessionHas('active_interview_context', 'interview');

        $this->assertDatabaseHas('interview_sessions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'game_level_id' => null,
            'status' => 'in_progress',
        ]);
    }

    public function test_interview_start_creates_questions_when_bank_is_empty(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $this->actingAs($user)
            ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
                'num_questions' => 5,
                'question_types' => ['Technical'],
            ]))
            ->assertRedirect(route('interview.session'));

        $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseCount('questions', 5);
        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'type' => 'Technical',
            'status' => 'active',
        ]);
        Question::where('interview_session_id', $session->id)
            ->pluck('question_text')
            ->each(fn (string $questionText) => $this->assertStringContainsString('Developer', $questionText));
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

    public function test_voice_session_save_recomputes_measurable_metrics(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->postJson(route('user.drills.voice.save'), [
                'category' => 'Behavioral',
                'prompt' => 'Tell me about a project.',
                'transcript' => 'Um I solved the issue clearly Um I solved the issue clearly',
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
            'transcript' => 'Um I solved the issue clearly',
            'wpm' => 6,
            'speaking_pace' => 6,
            'filler_words' => 1,
            'clarity_score' => 58,
            'confidence_score' => 60,
        ]);
    }

    public function test_voice_session_without_transcript_does_not_trust_client_scores(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->postJson(route('user.drills.voice.save'), [
                'category' => 'Behavioral',
                'transcript' => '',
                'duration_seconds' => 60,
                'wpm' => 180,
                'filler_words' => 0,
                'clarity_score' => 100,
                'confidence_score' => 100,
            ])
            ->assertOk();

        $this->assertDatabaseHas('voice_sessions', [
            'user_id' => $user->id,
            'transcript' => '',
            'wpm' => 0,
            'speaking_pace' => 0,
            'clarity_score' => 0,
            'confidence_score' => 0,
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

    public function test_interview_answer_records_copy_paste_and_ai_integrity_signals(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'question_id' => $question->id,
                'answer_text' => 'As an AI language model, I would leverage best practices to streamline a robust and comprehensive process for stakeholders while ensuring measurable outcomes and continuous improvement.',
                'paste_event_count' => 1,
                'pasted_character_count' => 180,
                'elapsed_seconds' => 6,
                'transcript_timeline' => json_encode([
                    ['at' => 5, 'event' => 'large_paste', 'words' => 24, 'chars' => 180, 'pasted_chars' => 180],
                ]),
            ])
            ->assertOk();

        $answer = InterviewAnswer::where('interview_session_id', $session->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertSame(1, $answer->paste_event_count);
        $this->assertSame(180, $answer->pasted_character_count);
        $this->assertGreaterThanOrEqual(70, $answer->ai_generated_likelihood);
        $this->assertTrue($answer->answer_integrity_flags['copy_paste_detected']);
        $this->assertTrue($answer->answer_integrity_flags['possible_ai_generated_answer']);
        $this->assertContains('large_paste_volume', $answer->answer_integrity_flags['signals']);
        $this->assertSame('flagged', $answer->audit_status);
        $this->assertStringContainsString('copy/paste activity detected', $answer->flagged_reason);
        $this->assertStringContainsString('possible AI-generated answer pattern detected', $answer->flagged_reason);
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
        $this->assertDatabaseHas('game_sessions', [
            'user_id' => $user->id,
            'game_level_id' => $level->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseCount('interview_sessions', 0);
    }

    public function test_game_answer_and_finish_use_separate_game_session_flow(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => 3]);
        $category = $this->category(['type' => 'game']);
        $level = $this->gameLevel($category, [
            'required_score' => 0,
            'xp_reward' => 125,
        ]);

        $this->actingAs($user)
            ->post(route('user.game.start', $level))
            ->assertRedirect(route('user.game.match'));

        $session = GameSession::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('user.game.answer'), [
                'game_session_id' => $session->id,
                'question_index' => 0,
                'answer_text' => 'I explained the situation task action and result with enough detail for this learning game.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->actingAs($user)
            ->post(route('user.game.finish'), [
                'game_session_id' => $session->id,
                'duration_seconds' => 45,
            ])
            ->assertRedirect(route('user.learning', ['category_id' => $category->id]))
            ->assertSessionHas('success')
            ->assertSessionHas('game_result', function (array $result) use ($level, $session): bool {
                return $result['game_session_id'] === $session->id
                    && $result['level_id'] === $level->id
                    && $result['status'] === 'passed'
                    && $result['required_score'] === 0
                    && $result['energy_spent'] === 1
                    && $result['energy_remaining'] === 2
                    && $result['xp_earned'] === 125;
            });

        $this->assertDatabaseHas('game_progress', [
            'user_id' => $user->id,
            'game_level_id' => $level->id,
            'status' => 'completed',
        ]);
        $this->assertGreaterThan(0, (int) GameProgress::where('user_id', $user->id)
            ->where('game_level_id', $level->id)
            ->value('best_score'));

        $this->assertDatabaseHas('game_sessions', [
            'id' => $session->id,
            'status' => 'completed',
            'duration_seconds' => 45,
        ]);
        $this->assertDatabaseHas('game_answers', [
            'game_session_id' => $session->id,
            'question_index' => 0,
        ]);
        $this->assertDatabaseCount('interview_sessions', 0);
        $this->assertDatabaseCount('interview_answers', 0);

        $profile = Profile::where('user_id', $user->id)->firstOrFail();
        $scoreCount = Score::count();
        $progressUpdatedAt = GameProgress::where('user_id', $user->id)
            ->where('game_level_id', $level->id)
            ->firstOrFail()
            ->updated_at;

        $this->actingAs($user)
            ->post(route('user.game.finish'), ['game_session_id' => $session->id])
            ->assertRedirect(route('user.learning', ['category_id' => $category->id]))
            ->assertSessionHas('success')
            ->assertSessionHas('game_result');

        $this->assertSame($scoreCount, Score::count());
        $this->assertSame($profile->experience_points, Profile::where('user_id', $user->id)->firstOrFail()->experience_points);
        $this->assertTrue($progressUpdatedAt->equalTo(
            GameProgress::where('user_id', $user->id)
                ->where('game_level_id', $level->id)
                ->firstOrFail()
                ->updated_at
        ));
    }

    public function test_regular_interview_finish_ignores_stale_game_level_session_key(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => 3]);
        $category = $this->category();
        $gameCategory = $this->category(['title' => 'Game Path', 'type' => 'game']);
        $level = $this->gameLevel($gameCategory);
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I built a deployment checklist and improved release quality with clearer ownership.',
            'response_mode' => 'text',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'unsupported',
                'game_level_id' => $level->id,
                'active_interview_context' => 'learning_game',
            ])
            ->post(route('interview.finish'), [
                'session_id' => $session->id,
                'duration_seconds' => 60,
            ])
            ->assertRedirect(route('user.review', $session->id))
            ->assertSessionMissing('game_result');

        $this->assertDatabaseMissing('game_progress', [
            'user_id' => $user->id,
            'game_level_id' => $level->id,
        ]);
    }

    public function test_game_match_rejects_regular_interview_even_with_stale_game_key(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $gameCategory = $this->category(['title' => 'Game Route', 'type' => 'game']);
        $level = $this->gameLevel($gameCategory);
        $session = $this->sessionFor($user, $category);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'game_level_id' => $level->id,
                'active_interview_context' => 'learning_game',
            ])
            ->get(route('user.game.match'))
            ->assertRedirect(route('user.learning'))
            ->assertSessionHas('error', 'No active Learning Game found.');
    }

    public function test_learning_page_renders_game_result_modal_actions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => 2]);
        $category = $this->category(['type' => 'game']);
        $level = $this->gameLevel($category, [
            'success_criteria' => "1. Give context.\n2. Explain action.\n3. State result.",
            'retry_hint' => 'Add a stronger measurable result.',
        ]);
        $session = GameSession::create([
            'user_id' => $user->id,
            'game_level_id' => $level->id,
            'status' => 'completed',
            'questions' => ['Describe a goal answer.'],
            'num_questions' => 1,
        ]);

        $this->actingAs($user)
            ->withSession([
                'game_result' => [
                    'game_session_id' => $session->id,
                    'level_id' => $level->id,
                    'level_number' => $level->level_number,
                    'level_title' => $level->title,
                    'skill_focus' => 'STAR Method',
                    'learning_objective' => 'Use a complete STAR answer.',
                    'success_criteria' => ['Give context.', 'Explain action.', 'State result.'],
                    'status' => 'failed',
                    'message' => 'You scored 65% and need 80% to clear this level.',
                    'score' => 65,
                    'required_score' => 80,
                    'points_to_goal' => 15,
                    'best_score' => 65,
                    'is_new_best' => true,
                    'xp_earned' => 100,
                    'energy_spent' => 1,
                    'energy_remaining' => 2,
                    'retry_hint' => 'Add a stronger measurable result.',
                    'retry_energy_cost' => 1,
                    'can_retry' => true,
                    'next_level' => null,
                    'goal_breakdown' => [
                        'averages' => [
                            'goal_coverage' => 60,
                            'clarity' => 70,
                        ],
                    ],
                ],
            ])
            ->get(route('user.learning', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('id="gameResultModal"', false)
            ->assertSee('Needs Retry')
            ->assertSee('15 more points needed')
            ->assertSee('Retry Level')
            ->assertSee('Goal Score Breakdown')
            ->assertDontSee('View Feedback')
            ->assertSee('Add a stronger measurable result.');
    }

    public function test_learning_game_session_renders_game_only_finish_modal(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => 3]);
        $category = $this->category(['type' => 'game']);
        $level = $this->gameLevel($category);
        $session = GameSession::create([
            'user_id' => $user->id,
            'game_level_id' => $level->id,
            'status' => 'in_progress',
            'num_questions' => 1,
            'questions' => ['Describe a goal answer.'],
            'response_mode' => 'hybrid',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_game_session_id' => $session->id,
                'game_level_id' => $level->id,
            ])
            ->get(route('user.game.match'))
            ->assertOk()
            ->assertSee('Finish Challenge')
            ->assertSee('id="challengeFinishModal"', false)
            ->assertSee('Scoring Challenge')
            ->assertDontSee('Finish Interview');
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

    private function sessionFor(User $user, Category $category, array $overrides = []): InterviewSession
    {
        return InterviewSession::create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'in_progress',
        ], $overrides));
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
