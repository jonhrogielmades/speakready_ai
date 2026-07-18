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
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReliabilityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_openai_feedback_uses_strict_evidence_linked_structured_output(): void
    {
        $answerText = 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.';
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => [
                        'content' => json_encode([
                            'per_question_feedback' => [[
                                'id' => 71,
                                'score' => 82,
                                'clarity_score' => 80,
                                'relevance_score' => 86,
                                'grammar_score' => 82,
                                'professionalism_score' => 80,
                                'star_applicable' => false,
                                'star_method_score' => 0,
                                'evidence_quotes' => ['I inspected the query plan, compared row estimates, and verified index usage'],
                                'ai_feedback' => 'You stated "I inspected the query plan, compared row estimates, and verified index usage", which directly supports a relevant diagnostic approach.',
                            ]],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $formatMethod = new \ReflectionMethod(AIService::class, 'feedbackResponseFormat');
        $formatMethod->setAccessible(true);
        $responseFormat = $formatMethod->invoke(null);
        $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
        $providerMethod->setAccessible(true);
        $providerResponse = $providerMethod->invokeArgs(null, [
            'openai',
            'Return evidence_quotes from UNTRUSTED TRANSCRIPT DATA JSON.',
            [
                'response_format' => $responseFormat,
                'model' => 'gpt-4o-mini',
                'timeout_seconds' => 5,
                'attempts' => 1,
            ],
        ]);
        $normalizeMethod = new \ReflectionMethod(AIService::class, 'normalizeFeedbackResponse');
        $normalizeMethod->setAccessible(true);
        $feedback = $normalizeMethod->invokeArgs(null, [$providerResponse, [[
            'id' => 71,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => $answerText,
        ]], []]);

        $this->assertSame('ai_evidence_validated', $feedback['per_question_feedback'][0]['evaluation_source']);
        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return data_get($payload, 'response_format.type') === 'json_schema'
                && data_get($payload, 'response_format.json_schema.strict') === true
                && data_get($payload, 'response_format.json_schema.schema.additionalProperties') === false
                && str_contains((string) data_get($payload, 'messages.1.content'), 'evidence_quotes')
                && str_contains((string) data_get($payload, 'messages.1.content'), 'UNTRUSTED TRANSCRIPT DATA JSON');
        });
    }

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
        $session = $this->sessionFor($user, $category, ['num_questions' => 2]);
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
            ->assertJsonPath('source_type', 'ai_adapted_source_backed');
        $this->assertTrue(str_contains($response->json('next_question_text'), 'owned a small release')
            && str_contains($response->json('next_question_text'), 'final question')
            && str_contains($response->json('next_question_text'), 'Developer'));

        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $firstQuestion->id,
        ]);
        $this->assertSame(1, InterviewAnswer::where('interview_session_id', $session->id)->count());
    }

    public function test_interview_reuses_existing_next_question_without_generating_duplicate(): void
    {
        Http::fake();
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');

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

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.chatReply'), [
                'question_id' => $firstQuestion->id,
                'answer_text' => 'I owned a release, coordinated QA, and communicated the rollback plan.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('next_question_id', $secondQuestion->id)
            ->assertJsonPath('next_question_text', $secondQuestion->question_text);

        Http::assertNothingSent();
        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, InterviewAnswer::where('interview_session_id', $session->id)->count());
    }

    public function test_interview_chat_reply_cannot_generate_after_final_question(): void
    {
        Http::fake();

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category, ['num_questions' => 1]);
        $finalQuestion = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Why are you the right fit for this role?',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.chatReply'), [
                'question_id' => $finalQuestion->id,
                'answer_text' => 'I have relevant experience, communicate clearly, and can deliver the role outcomes.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'interview_completed' => true,
            ]);

        Http::assertNothingSent();
        $this->assertSame(1, Question::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, InterviewAnswer::where('interview_session_id', $session->id)->count());
    }

    public function test_abort_interview_deletes_unfinished_session_data_and_clears_active_keys(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category, [
            'num_questions' => 2,
            'session_state' => json_encode(['has_started' => true, 'currentQIdx' => 0]),
        ]);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'Draft answer that should be removed.',
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 50,
            'relevance_score' => 50,
            'grammar_score' => 50,
            'professionalism_score' => 50,
            'overall_readiness_score' => 50,
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Temporary strength.',
            'weaknesses' => 'Temporary weakness.',
            'improvement_suggestions' => 'Temporary suggestion.',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
                'active_interview_context' => 'interview',
            ])
            ->postJson(route('interview.abort'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'redirect_url' => route('interview.setup'),
            ])
            ->assertSessionMissing('active_interview_id')
            ->assertSessionMissing('active_interview_provider')
            ->assertSessionMissing('active_interview_context');

        $this->assertDatabaseMissing('interview_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('interview_answers', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
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
            ->assertSee('abortInterviewSession', false)
            ->assertSee('onclick="abortInterviewSession()"', false)
            ->assertSee(route('interview.abort'), false)
            ->assertSee('openingHasPlayed', false)
            ->assertSee('startInterviewSession();', false)
            ->assertSee('feedbackLoadingOverlay', false)
            ->assertSee('showFeedbackLoadingState', false)
            ->assertSee('feedbackLoadingStage', false)
            ->assertSee('feedbackSubmissionInFlight', false)
            ->assertSee("'Accept': 'application/json'", false)
            ->assertDontSee('onclick="concludeAndFinishInterview({ saveDraft: true })"', false)
            ->assertSee('conversation_context', false)
            ->assertSee('sessionTargetPosition', false);
    }

    public function test_interview_finish_is_fast_idempotent_and_creates_complete_local_feedback(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'During a delayed release, I owned the checklist, coordinated the missing approvals, and delivered the deployment after documenting the final result.',
            'response_mode' => 'text',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), [
                'session_id' => $session->id,
                'duration_seconds' => 75,
            ]);

        $response->assertOk()->assertJsonPath('redirect_url', route('user.review', $session));
        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertDatabaseHas('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseHas('feedback', ['interview_session_id' => $session->id]);
        $this->assertNotEmpty($answer->fresh()->ai_feedback);

        $profileAfterFirstFinish = Profile::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
        $this->assertSame(
            $profileAfterFirstFinish->total_sessions,
            Profile::where('user_id', $user->id)->value('total_sessions')
        );
    }

    public function test_stale_feedback_processing_state_can_recover_without_duplicate_records(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['status' => 'processing']);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the support handoff, documented repeated issues, coordinated the update, and confirmed the final result with my supervisor.',
            'response_mode' => 'text',
        ]);
        $session->timestamps = false;
        $session->updated_at = now()->subMinutes(3);
        $session->save();

        $this->actingAs($user)
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
    }

    public function test_retry_answer_persists_the_evidence_based_scoring_confidence(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['status' => 'completed']);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'My first answer did not include enough specific evidence.',
            'response_mode' => 'text',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_provider' => 'local'])
            ->postJson(route('interview.answer.retry', $answer), [
                'answer_text' => 'During a difficult project, I diagnosed the release issue, coordinated the rollback, and completed the recovery within ten minutes.',
                'response_mode' => 'text',
            ]);

        $response->assertOk()->assertJsonPath('scoring_confidence', 50);
        $retry = InterviewAnswer::where('retry_of_answer_id', $answer->id)->firstOrFail();
        $this->assertSame(50, (int) $retry->scoring_confidence);
        $this->assertSame('candidate_facts', $retry->improved_answer_source);
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
        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertSee('feedback-report-meta', false)
            ->assertSee('feedback-hero-content', false)
            ->assertSee('answer-review-body', false);
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
