<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReliabilityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_question_analytics_uses_recorded_answers(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $question = $this->question($category);
        $session = $this->sessionFor($user, $category);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'First recorded answer.',
            'score' => 70,
        ]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'Second recorded answer.',
            'score' => 90,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.questions.analytics', $question))
            ->assertOk()
            ->assertJson([
                'used_count' => 2,
                'average_score' => 80,
                'has_score_data' => true,
            ]);
    }

    public function test_admin_question_generation_has_deterministic_fallback_without_ai_credentials(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $category = $this->category(['title' => 'Behavioral']);

        $this->actingAs($admin)
            ->postJson(route('admin.questions.ai-generate'), [
                'category_id' => $category->id,
                'position' => 'Software Engineer',
                'difficulty' => 'Hard',
                'ai_provider' => 'local',
            ])
            ->assertOk()
            ->assertJson([
                'source' => 'fallback',
            ])
            ->assertJsonPath('question_text', 'For a Software Engineer role, describe a complex or high-pressure situation where you used Behavioral. What was your responsibility, what actions did you take, and what measurable result followed?');
    }

    public function test_user_ai_generated_start_question_is_saved_to_admin_question_bank(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Behavioral']);
        $questionText = 'Tell me about a production issue you diagnosed and resolved.';
        $roleAlignedQuestionText = 'For your target position of Backend Developer, tell me about a production issue you diagnosed and resolved.';

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['questions' => [$questionText]]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('interview.start'), [
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => 'Backend Developer',
                'num_questions' => 5,
                'response_mode' => 'text',
                'time_limit' => 0,
                'ai_provider' => 'openai',
            ])
            ->assertRedirect(route('interview.session'));

        $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'category_id' => $category->id,
            'question_text' => $roleAlignedQuestionText,
        ]);

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => null,
            'category_id' => $category->id,
            'question_text' => $roleAlignedQuestionText,
            'source_type' => 'ai_adapted_source_backed',
        ]);
    }

    public function test_user_ai_follow_up_question_is_saved_to_admin_question_bank(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        $followUpText = 'What tradeoff would you make differently if you handled that project again?';
        $roleAlignedFollowUpText = 'For your target position of Developer, what tradeoff would you make differently if you handled that project again?';
        $capturedPrompt = '';

        Http::fake([
            'api.openai.com/*' => function ($request) use ($followUpText, &$capturedPrompt) {
                $capturedPrompt = data_get($request->data(), 'messages.1.content', '');

                return Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => $followUpText,
                            ],
                        ],
                    ],
                ], 200);
            },
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.chatReply'), [
                'question_id' => $question->id,
                'answer_text' => 'I coordinated the release, found the issue, and shipped a fix.',
                'conversation_context' => json_encode([
                    ['role' => 'interviewer', 'text' => 'Tell me about a release you owned.'],
                    ['role' => 'user', 'text' => 'I coordinated QA with the deployment team before launch.'],
                ]),
                'response_mode' => 'text',
                'ai_provider' => 'openai',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'next_question_text' => $roleAlignedFollowUpText,
            ]);

        $this->assertStringContainsString('RECENT INTERVIEW CHAT JSON', $capturedPrompt);
        $this->assertStringContainsString('deployment team', $capturedPrompt);
        $this->assertStringContainsString('LATEST CANDIDATE ANSWER TO RESPOND TO', $capturedPrompt);

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'category_id' => $category->id,
            'question_text' => $roleAlignedFollowUpText,
        ]);

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => null,
            'category_id' => $category->id,
            'question_text' => $roleAlignedFollowUpText,
            'source_type' => 'ai_adapted_source_backed',
        ]);
    }

    public function test_interview_advances_with_answer_based_local_followup_when_ai_followups_are_disabled(): void
    {
        Setting::setVal('int_follow_up', false, 'interview', 'boolean');

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category, ['num_questions' => 2]);
        $firstQuestion = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a release you owned.',
        ]);
        $secondQuestion = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'How would you verify a production fix before handoff?',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'local',
            ])
            ->postJson(route('interview.chatReply'), [
                'question_id' => $firstQuestion->id,
                'answer_text' => 'I owned a small release, coordinated QA, and communicated the rollback plan with the deployment team before launch.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source_type', 'ai_adapted_source_backed')
            ->assertJsonPath('next_question_text', fn (string $text) => str_contains($text, 'You mentioned you owned a small release')
                && str_contains($text, 'What result')
                && str_contains($text, 'Developer role'));

        $this->assertNotSame($secondQuestion->id, $response->json('next_question_id'));

        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $firstQuestion->id,
        ]);
        $this->assertSame(1, InterviewAnswer::where('interview_session_id', $session->id)->count());
    }

    public function test_interview_session_renders_human_opening_and_closing_conversation_flow(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category, [
            'target_position' => 'Developer',
            'num_questions' => 2,
            'live_feedback_mode' => 'real_interview',
        ]);
        $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a system you improved.',
        ]);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->get(route('interview.session'))
            ->assertOk()
            ->assertSee('openingConversationText', false)
            ->assertSee('beginOpeningConversation', false)
            ->assertSee('closingConversationText', false)
            ->assertSee('concludeAndFinishInterview', false)
            ->assertSee('playClosingConversationAndSubmit', false)
            ->assertSee('onclick="concludeAndFinishInterview({ saveDraft: true })"', false)
            ->assertSee('saveCurrentAnswer(false, false)', false)
            ->assertSee('conversation_context', false)
            ->assertSee('sessionTargetPosition', false);
    }

    public function test_review_page_does_not_render_unrecorded_delivery_or_comparison_metrics(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $question = $this->question($category);
        $session = $this->sessionFor($user, $category, ['status' => 'completed']);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I led a release planning meeting and coordinated the deployment checklist.',
            'ai_feedback' => 'Specific feedback based on the submitted answer.',
            'score' => 75,
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 75,
            'relevance_score' => 75,
            'grammar_score' => 75,
            'professionalism_score' => 75,
            'overall_readiness_score' => 75,
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Clear enough to understand.',
            'weaknesses' => 'Needs more measurable evidence.',
            'improvement_suggestions' => 'Add concrete results.',
        ]);

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('No previous scored session is available for comparison yet.')
            ->assertDontSee('Speaking Pace')
            ->assertDontSee('135 WPM')
            ->assertDontSee('STAR Framework Analysis');
    }

    public function test_admin_user_detail_endpoint_returns_real_stats_and_activity(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'target_position' => 'Data Analyst',
        ]);
        Profile::create(['user_id' => $user->id, 'current_streak' => 3]);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['status' => 'completed']);
        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 84,
            'relevance_score' => 84,
            'grammar_score' => 84,
            'professionalism_score' => 84,
            'overall_readiness_score' => 84,
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'interview_completed',
            'description' => 'Completed a recorded interview.',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.users.show', $user))
            ->assertOk()
            ->assertJsonPath('user.target_position', 'Data Analyst')
            ->assertJsonPath('stats.completed_interviews', 1)
            ->assertJsonPath('stats.average_score', 84)
            ->assertJsonPath('stats.highest_score', 84)
            ->assertJsonPath('stats.current_streak', 3)
            ->assertJsonPath('activities.0.text', 'Completed a recorded interview.');
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Communication',
            'description' => 'Communication questions',
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
}
