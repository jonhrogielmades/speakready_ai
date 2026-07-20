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
use App\Services\EvidenceBasedCoachingService;
use App\Services\TrustworthyAssessmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
                                'question_focus' => 'How would you diagnose a slow database query?',
                                'answer_alignment' => 'directly_addressed',
                                'missing_criteria' => [],
                                'ai_feedback' => 'For "How would you diagnose a slow database query?", you stated "I inspected the query plan, compared row estimates, and verified index usage", which directly supports a relevant diagnostic approach.',
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

    public function test_learning_game_feedback_is_distinct_and_question_bound(): void
    {
        $controller = app(\App\Http\Controllers\InterviewController::class);
        $method = new \ReflectionMethod($controller, 'scoreLearningGameAnswer');
        $method->setAccessible(true);
        $level = new \App\Models\GameLevel([
            'skill_focus' => 'Direct Answering',
            'learning_objective' => 'Answer each prompt directly with truthful supporting evidence.',
            'success_criteria' => '1. Direct answer 2. Relevant evidence 3. Clear next point',
        ]);
        $sameAnswer = 'I organize complex releases with dependency checklists and verify each handoff before launch.';
        $strength = $method->invoke($controller, [
            'id' => 201,
            'question' => 'What is your greatest strength?',
            'question_type' => 'Personal',
            'answer' => $sameAnswer,
        ], $level, []);
        $salary = $method->invoke($controller, [
            'id' => 202,
            'question' => 'What salary range are you expecting?',
            'question_type' => 'Personal',
            'answer' => $sameAnswer,
        ], $level, []);

        $this->assertNotSame($strength['ai_feedback'], $salary['ai_feedback']);
        $this->assertNotSame($strength['follow_up_question'], $salary['follow_up_question']);
        $this->assertStringContainsString('What is your greatest strength?', $strength['ai_feedback']);
        $this->assertStringContainsString('What salary range are you expecting?', $salary['ai_feedback']);
        $this->assertSame([$sameAnswer], $strength['evidence_quotes']);
        $this->assertSame([$sameAnswer], $salary['evidence_quotes']);
        $this->assertContains($strength['answer_alignment'], [
            'directly_addressed', 'partially_addressed', 'not_addressed', 'insufficient_evidence',
        ]);
        $this->assertContains($salary['answer_alignment'], [
            'directly_addressed', 'partially_addressed', 'not_addressed', 'insufficient_evidence',
        ]);
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

    public function test_interview_answers_brief_candidate_question_then_continues_interview(): void
    {
        Setting::setVal('int_follow_up', false, 'interview', 'boolean');

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category, ['num_questions' => 2]);
        $firstQuestion = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Please introduce yourself.',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'local',
            ])
            ->postJson(route('interview.chatReply'), [
                'question_id' => $firstQuestion->id,
                'answer_text' => 'My name is John, and I am based in Manila. By the way, what is your name?',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $nextQuestion = (string) $response->json('next_question_text');
        $this->assertStringContainsString('I am Mia, nice to meet you.', $nextQuestion);
        $this->assertSame(1, substr_count($nextQuestion, '?'));
        $this->assertStringContainsString('Developer', $nextQuestion);
    }

    public function test_interview_replaces_unanswered_fixed_next_question_with_answer_based_followup(): void
    {
        Http::fake();
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

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'local',
            ])
            ->postJson(route('interview.chatReply'), [
                'question_id' => $firstQuestion->id,
                'answer_text' => 'I owned a release, coordinated QA, and communicated the rollback plan.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['next_question_id' => $secondQuestion->id])
            ->assertJsonMissing(['next_question_text' => $secondQuestion->question_text])
            ->assertJsonPath('source_type', 'ai_adapted_source_backed');

        Http::assertNothingSent();
        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
        $this->assertDatabaseMissing('questions', ['id' => $secondQuestion->id]);
        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'source_type' => 'ai_adapted_source_backed',
        ]);
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
            ->assertSee('firstQuestionIntroText', false)
            ->assertSee('Heres your first questions', false)
            ->assertSee('first_question_intro', false)
            ->assertSee('startInterviewSession();', false)
            ->assertSee('finishTransitionOverlay', false)
            ->assertSee('setFinishTransitionVisible', false)
            ->assertSee('finishRetryButton', false)
            ->assertSee('retryFinishInterview', false)
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
        $savedAnswer = $answer->fresh();
        $savedFeedback = Feedback::where('interview_session_id', $session->id)->firstOrFail();
        $this->assertNotEmpty($savedAnswer->ai_feedback);
        $this->assertNotEmpty($savedAnswer->coaching_feedback);
        $this->assertSame('not_measured', data_get($savedAnswer->coaching_feedback, 'delivery.status'));
        $this->assertNotEmpty(data_get($savedAnswer->coaching_feedback, 'question.tip'));
        $this->assertSame($savedAnswer->id, data_get($savedAnswer->coaching_feedback, 'content_alignment.answer_id'));
        $this->assertSame($question->id, data_get($savedAnswer->coaching_feedback, 'content_alignment.question_id'));
        $this->assertSame($question->question_text, data_get($savedAnswer->coaching_feedback, 'content_alignment.question'));
        $this->assertContains(
            data_get($savedAnswer->coaching_feedback, 'content_alignment.status'),
            ['directly_answered', 'partially_answered', 'low_relevance']
        );
        $this->assertNotEmpty(data_get($savedAnswer->coaching_feedback, 'content_alignment.evidence_quotes'));
        $this->assertNotEmpty(data_get($savedAnswer->coaching_feedback, 'content_alignment.what_worked'));
        $this->assertNotEmpty(data_get($savedAnswer->coaching_feedback, 'content_alignment.improvement_focus'));
        $this->assertNotEmpty(data_get($savedAnswer->coaching_feedback, 'content_alignment.action'));
        $this->assertNotEmpty(data_get($savedAnswer->coaching_feedback, 'content_alignment.next_attempt_steps'));
        $this->assertNotEmpty(data_get($savedAnswer->coaching_feedback, 'content_alignment.success_check'));
        $this->assertNotEmpty($savedFeedback->coaching_summary);
        $this->assertNotEmpty(data_get($savedFeedback->coaching_summary, 'content_overview'));
        $this->assertNotEmpty(data_get($savedFeedback->coaching_summary, 'question_improvements'));

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Answer-to-Question Relevance')
            ->assertSee($question->question_text)
            ->assertSee('Useful starting point')
            ->assertSee('Improve next')
            ->assertSee('Next-attempt checklist')
            ->assertSee('Done when')
            ->assertSee('Question-by-question improvement map')
            ->assertSee('Question coverage')
            ->assertDontSee('â€œ', false);

        $session->update(['is_public' => true, 'share_token' => 'alignment-review-token']);
        $this->get(route('shared.review', 'alignment-review-token'))
            ->assertOk()
            ->assertSee('Answer-to-Question Relevance')
            ->assertSee($question->question_text)
            ->assertSee('Useful starting point')
            ->assertSee('Improve next')
            ->assertSee('Next-attempt checklist')
            ->assertSee('Done when')
            ->assertSee('Question-by-question improvement map')
            ->assertSee('Question coverage')
            ->assertDontSee('â€œ', false);

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

    public function test_interview_finish_tolerates_missing_feedback_coaching_summary_column(): void
    {
        Http::preventStrayRequests();
        if (Schema::hasColumn('feedback', 'coaching_summary')) {
            Schema::table('feedback', function (Blueprint $table): void {
                $table->dropColumn('coaching_summary');
            });
        }

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the release checklist, coordinated QA approval, and documented the final handoff result.',
            'response_mode' => 'text',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'local',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Evidence-Based Coaching Summary')
            ->assertSee('Question coverage')
            ->assertSee($question->question_text);
    }

    public function test_user_review_repairs_missing_coaching_report_data_without_ai_refresh(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['status' => 'completed']);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Describe how you handled a delayed release.',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I coordinated QA approval, updated the release checklist, and documented the successful handoff.',
            'response_mode' => 'text',
            'ai_feedback' => 'The answer included relevant ownership and handoff evidence.',
            'score' => 78,
            'relevance_score' => 78,
            'scoring_confidence' => 80,
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'score_version' => TrustworthyAssessmentService::SCORE_VERSION,
            'clarity_score' => 78,
            'relevance_score' => 78,
            'grammar_score' => 78,
            'professionalism_score' => 78,
            'overall_readiness_score' => 78,
            'rubric' => ['version' => TrustworthyAssessmentService::SCORE_VERSION],
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'The answer showed ownership.',
            'weaknesses' => 'The result could be more measurable.',
            'improvement_suggestions' => 'Add a concrete result metric.',
        ]);

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Evidence-Based Coaching Summary')
            ->assertSee('Answer-to-Question Relevance')
            ->assertSee('Question coverage')
            ->assertSee($question->question_text);

        $this->assertSame(
            EvidenceBasedCoachingService::VERSION,
            data_get($answer->fresh()->coaching_feedback, 'version')
        );
        $this->assertSame(
            EvidenceBasedCoachingService::VERSION,
            data_get(Feedback::where('interview_session_id', $session->id)->firstOrFail()->coaching_summary, 'version')
        );
    }

    public function test_repair_feedback_coaching_command_backfills_missing_report_data(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['status' => 'completed']);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a time you improved a team process.',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I mapped the handoff issue, wrote a checklist, and confirmed the new process reduced missed approvals.',
            'response_mode' => 'text',
            'ai_feedback' => 'The answer connected the action to a process improvement.',
            'score' => 82,
            'relevance_score' => 82,
            'scoring_confidence' => 80,
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'score_version' => TrustworthyAssessmentService::SCORE_VERSION,
            'clarity_score' => 82,
            'relevance_score' => 82,
            'grammar_score' => 82,
            'professionalism_score' => 82,
            'overall_readiness_score' => 82,
            'rubric' => ['version' => TrustworthyAssessmentService::SCORE_VERSION],
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'The answer used a concrete process example.',
            'weaknesses' => 'The measurable outcome could be clearer.',
            'improvement_suggestions' => 'State the before-and-after result.',
        ]);

        $this->artisan('app:repair-feedback-coaching', ['--limit' => 0])
            ->assertExitCode(0);

        $this->assertSame(
            EvidenceBasedCoachingService::VERSION,
            data_get($answer->fresh()->coaching_feedback, 'version')
        );
        $feedback = Feedback::where('interview_session_id', $session->id)->firstOrFail();
        $this->assertSame(EvidenceBasedCoachingService::VERSION, data_get($feedback->coaching_summary, 'version'));
        $this->assertNotEmpty(data_get($feedback->coaching_summary, 'question_improvements'));
    }

    public function test_retry_answer_returns_server_rendered_evidence_based_coaching(): void
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

        $response->assertOk()
            ->assertJsonPath('scoring_confidence', 50)
            ->assertJsonStructure([
                'coaching_feedback' => ['content_alignment'],
                'coaching_html',
            ]);
        $retry = InterviewAnswer::where('retry_of_answer_id', $answer->id)->firstOrFail();
        $this->assertSame(50, (int) $retry->scoring_confidence);
        $this->assertSame('candidate_facts', $retry->improved_answer_source);
        $this->assertSame($retry->id, data_get($retry->coaching_feedback, 'content_alignment.answer_id'));
        $this->assertSame($question->id, data_get($retry->coaching_feedback, 'content_alignment.question_id'));
        $this->assertNotEmpty(data_get($retry->coaching_feedback, 'content_alignment.evidence_quotes'));
        $this->assertStringContainsString('Evidence-Based Coaching', (string) $response->json('coaching_html'));
        $this->assertStringContainsString('Answer-to-Question Relevance', (string) $response->json('coaching_html'));
        $this->assertStringContainsString($question->question_text, (string) $response->json('coaching_html'));

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee("typeof data.coaching_html === 'string'", false)
            ->assertSee('${coachingHtml}', false);
    }

    public function test_shared_review_shows_session_summary_and_distinct_retry_coaching_without_retry_controls(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, [
            'status' => 'completed',
            'is_public' => true,
            'share_token' => 'shared-retry-coaching-token',
        ]);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Describe how you resolved a difficult release issue.',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I initially described the release issue without enough evidence.',
            'response_mode' => 'text',
            'ai_feedback' => 'Original answer feedback.',
            'score' => 45,
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'retry_of_answer_id' => $answer->id,
            'attempt_number' => 1,
            'question_id' => $question->id,
            'answer_text' => 'I diagnosed the failed release and coordinated a rollback.',
            'response_mode' => 'text',
            'ai_feedback' => 'First retry feedback: the response explained the diagnosis but not the verified outcome.',
            'score' => 64,
            'coaching_feedback' => [
                'analysis_status' => ['content' => 'scored', 'alignment' => 'partially_answered'],
                'content_alignment' => [
                    'status' => 'partially_answered',
                    'status_label' => 'Partially answered',
                    'question' => $question->question_text,
                    'relevance_score' => 64,
                    'observation' => 'First retry alignment: diagnosis and rollback were relevant, but the result was missing.',
                    'evidence_quotes' => ['I diagnosed the failed release and coordinated a rollback.'],
                    'missing_points' => ['The verified recovery result was not stated.'],
                    'action' => 'First retry action: add the verified recovery result.',
                ],
            ],
        ]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'retry_of_answer_id' => $answer->id,
            'attempt_number' => 2,
            'question_id' => $question->id,
            'answer_text' => 'I diagnosed the failed release, coordinated a rollback, and verified service recovery with monitoring.',
            'response_mode' => 'text',
            'ai_feedback' => 'Second retry feedback: the response added a verified recovery check.',
            'score' => 86,
            'coaching_feedback' => [
                'analysis_status' => ['content' => 'scored', 'alignment' => 'directly_answered'],
                'content_alignment' => [
                    'status' => 'directly_answered',
                    'status_label' => 'Directly answered',
                    'question' => $question->question_text,
                    'relevance_score' => 86,
                    'observation' => 'Second retry alignment: the response covered diagnosis, action, and verification.',
                    'evidence_quotes' => ['I diagnosed the failed release, coordinated a rollback, and verified service recovery with monitoring.'],
                    'missing_points' => [],
                    'action' => 'Second retry action: retain this complete sequence in future answers.',
                ],
            ],
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'score_version' => TrustworthyAssessmentService::SCORE_VERSION,
            'clarity_score' => 72,
            'relevance_score' => 72,
            'grammar_score' => 72,
            'professionalism_score' => 72,
            'overall_readiness_score' => 72,
            'rubric' => ['version' => TrustworthyAssessmentService::SCORE_VERSION],
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'The retries became more specific.',
            'weaknesses' => 'The original answer lacked a verified result.',
            'improvement_suggestions' => 'Keep the action and verification sequence.',
            'coaching_summary' => [
                'version' => EvidenceBasedCoachingService::VERSION,
                'observations' => ['Shared summary observation: both retries added relevant evidence.'],
                'priority_actions' => [[
                    'area' => 'Release example',
                    'observation' => 'The final retry included verification.',
                    'action' => 'Shared summary action: keep the verified outcome in the final answer.',
                ]],
                'coverage' => ['answers' => 1, 'delivery_measured' => 0],
                'transparency_note' => 'Shared summary transparency note.',
            ],
        ]);

        $this->get(route('shared.review', 'shared-retry-coaching-token'))
            ->assertOk()
            ->assertSee('Evidence-Based Coaching Summary')
            ->assertSee('Shared summary observation: both retries added relevant evidence.')
            ->assertSee('Shared summary action: keep the verified outcome in the final answer.')
            ->assertSee('Retry Attempts')
            ->assertSeeInOrder(['Attempt 1', 'Attempt 2'])
            ->assertSee('First retry feedback: the response explained the diagnosis but not the verified outcome.')
            ->assertSee('First retry alignment: diagnosis and rollback were relevant, but the result was missing.')
            ->assertSee('First retry action: add the verified recovery result.')
            ->assertSee('Second retry feedback: the response added a verified recovery check.')
            ->assertSee('Second retry alignment: the response covered diagnosis, action, and verification.')
            ->assertSee('Second retry action: retain this complete sequence in future answers.')
            ->assertDontSee('Retry This Answer')
            ->assertDontSee('Practice Attempt');
    }

    public function test_user_review_refreshes_stale_rubric_score_metadata_on_open(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['status' => 'completed']);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a time you improved a support handoff.',
        ]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the support handoff, documented repeated issues, coordinated the update, and confirmed the final result with my supervisor.',
            'response_mode' => 'text',
            'score' => 60,
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'score_version' => 1,
            'clarity_score' => 60,
            'relevance_score' => 60,
            'grammar_score' => 60,
            'professionalism_score' => 60,
            'overall_readiness_score' => 60,
            'rubric' => ['version' => 1],
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Legacy summary.',
            'weaknesses' => 'Legacy gap.',
            'improvement_suggestions' => 'Legacy next step.',
            'coaching_summary' => ['version' => EvidenceBasedCoachingService::VERSION],
        ]);

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Rubric v'.TrustworthyAssessmentService::SCORE_VERSION);

        $score = Score::where('interview_session_id', $session->id)->firstOrFail();
        $answer = InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail();
        $feedback = Feedback::where('interview_session_id', $session->id)->firstOrFail();

        $this->assertSame(TrustworthyAssessmentService::SCORE_VERSION, $score->score_version);
        $this->assertSame(TrustworthyAssessmentService::SCORE_VERSION, data_get($score->rubric, 'version'));
        $this->assertSame(EvidenceBasedCoachingService::VERSION, data_get($feedback->coaching_summary, 'version'));
        $this->assertNotEmpty($answer->evidence_map);
        $this->assertNotEmpty($answer->rubric_level);
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

    public function test_unavailable_relevance_is_rendered_as_not_scored_instead_of_zero_performance(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $question = $this->question($category, ['question_text' => 'What is one weakness you are improving?']);
        $session = $this->sessionFor($user, $category, ['status' => 'completed']);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'Public speaking.',
            'response_mode' => 'text',
            'score' => 0,
            'ai_feedback' => 'The response was too short for a dependable evaluation.',
            'coaching_feedback' => [
                'analysis_status' => ['content' => 'limited_evidence', 'alignment' => 'insufficient_evidence'],
                'content_alignment' => [
                    'status' => 'insufficient_evidence',
                    'status_label' => 'Not enough evidence',
                    'question' => $question->question_text,
                    'relevance_score' => 0,
                    'observation' => 'The response was too short for a dependable relevance judgment.',
                    'what_worked' => 'A response was started.',
                    'improvement_focus' => 'Add specific, relevant detail.',
                    'missing_points' => ['The response did not contain enough relevant detail.'],
                    'action' => 'Explain the weakness, its effect, and the truthful improvement action.',
                    'next_attempt_steps' => ['Name the weakness.', 'Explain the improvement action.'],
                    'success_check' => 'The retry contains enough relevant detail to assess.',
                ],
            ],
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 0,
            'relevance_score' => 0,
            'grammar_score' => 0,
            'professionalism_score' => 0,
            'overall_readiness_score' => 0,
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'No dependable strength was inferred.',
            'weaknesses' => 'The response needs more evidence.',
            'improvement_suggestions' => 'Expand the response before relying on a score.',
        ]);

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Not enough evidence')
            ->assertSee('Not scored')
            ->assertDontSee('Score: 0')
            ->assertDontSee('0% relevance');
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
