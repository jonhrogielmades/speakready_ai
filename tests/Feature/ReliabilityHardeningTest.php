<?php

namespace Tests\Feature;

use App\Http\Controllers\InterviewController;
use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\GameLevel;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\Setting;
use App\Models\User;
use App\Services\AIService;
use App\Services\EvidenceBasedCoachingService;
use App\Services\QuestionDatasetProvider;
use App\Services\QuestionIntentService;
use App\Services\TrustworthyAssessmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReliabilityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnvValues = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnvValues as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        parent::tearDown();
    }

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
                                'better_sample_answer' => 'I would answer: I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
                                'follow_up_question' => 'What final result or detail from this answer would make it stronger?',
                                'coaching' => [
                                    'keep' => 'Keep the query plan and row-estimate detail for "How would you diagnose a slow database query?".',
                                    'improve' => 'Add the final query result or check for "How would you diagnose a slow database query?".',
                                    'next_try' => 'Answer "How would you diagnose a slow database query?" by linking the diagnostic steps to the verified result.',
                                    'next_attempt_steps' => [
                                        'Start with the database symptom you would check first.',
                                        'Use "I inspected the query plan, compared row estimates, and verified index usage" as support.',
                                    ],
                                    'success_check' => 'The retry links the database diagnosis to a verified query result.',
                                ],
                            ]],
                            'session_feedback' => [
                                'strengths' => 'The AI review used saved answer details such as "I inspected the query plan, compared row estimates, and verified index usage" to identify what worked.',
                                'weaknesses' => 'The answer could add the final result from the same database work.',
                                'improvement_suggestions' => 'Keep the diagnostic steps and add the outcome only if it is true.',
                            ],
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
        $controller = app(InterviewController::class);
        $method = new \ReflectionMethod($controller, 'scoreLearningGameAnswer');
        $method->setAccessible(true);
        $level = new GameLevel([
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

    public function test_default_ai_provider_prefers_hugging_face_when_no_primary_provider_is_set(): void
    {
        $previousPriority = getenv('AI_DEFAULT_PROVIDER_PRIORITY');
        putenv('AI_DEFAULT_PROVIDER_PRIORITY=huggingface,gemini,groq,openrouter,cohere');
        $_ENV['AI_DEFAULT_PROVIDER_PRIORITY'] = 'huggingface,gemini,groq,openrouter,cohere';
        $_SERVER['AI_DEFAULT_PROVIDER_PRIORITY'] = 'huggingface,gemini,groq,openrouter,cohere';

        try {
            $this->aiProvider('Hugging Face', [
                'api_endpoint' => 'https://router.huggingface.co/v1',
            ]);
            $this->aiProvider('OpenAI', [
                'api_endpoint' => 'https://api.openai.com/v1',
            ]);

            $this->assertSame('huggingface', AIService::defaultProviderKey());
        } finally {
            if ($previousPriority === false) {
                putenv('AI_DEFAULT_PROVIDER_PRIORITY');
                unset($_ENV['AI_DEFAULT_PROVIDER_PRIORITY'], $_SERVER['AI_DEFAULT_PROVIDER_PRIORITY']);
            } else {
                putenv("AI_DEFAULT_PROVIDER_PRIORITY={$previousPriority}");
                $_ENV['AI_DEFAULT_PROVIDER_PRIORITY'] = $previousPriority;
                $_SERVER['AI_DEFAULT_PROVIDER_PRIORITY'] = $previousPriority;
            }
        }
    }

    public function test_default_ai_provider_honors_active_primary_provider(): void
    {
        $this->aiProvider('OpenAI', [
            'api_endpoint' => 'https://api.openai.com/v1',
        ]);
        $this->aiProvider('Gemini', [
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'is_primary' => true,
        ]);

        $this->assertSame('gemini', AIService::defaultProviderKey());
    }

    public function test_default_ai_provider_skips_inactive_primary_provider(): void
    {
        $previousPriority = getenv('AI_DEFAULT_PROVIDER_PRIORITY');
        putenv('AI_DEFAULT_PROVIDER_PRIORITY=openai');
        $_ENV['AI_DEFAULT_PROVIDER_PRIORITY'] = 'openai';
        $_SERVER['AI_DEFAULT_PROVIDER_PRIORITY'] = 'openai';

        try {
            $this->aiProvider('Hugging Face', [
                'api_endpoint' => 'https://router.huggingface.co/v1',
                'status' => 'inactive',
                'is_primary' => true,
            ]);
            $this->aiProvider('OpenAI', [
                'api_endpoint' => 'https://api.openai.com/v1',
            ]);

            $this->assertSame('openai', AIService::defaultProviderKey());
            $this->assertSame('OpenAI', AiProvider::safeActiveProviderName());
        } finally {
            if ($previousPriority === false) {
                putenv('AI_DEFAULT_PROVIDER_PRIORITY');
                unset($_ENV['AI_DEFAULT_PROVIDER_PRIORITY'], $_SERVER['AI_DEFAULT_PROVIDER_PRIORITY']);
            } else {
                putenv("AI_DEFAULT_PROVIDER_PRIORITY={$previousPriority}");
                $_ENV['AI_DEFAULT_PROVIDER_PRIORITY'] = $previousPriority;
                $_SERVER['AI_DEFAULT_PROVIDER_PRIORITY'] = $previousPriority;
            }
        }
    }

    public function test_ai_question_generation_prompt_includes_storage_backed_reliable_question_bank(): void
    {
        $capturedPrompt = '';
        Http::fake([
            'api.openai.com/*' => function ($request) use (&$capturedPrompt) {
                $capturedPrompt = data_get($request->data(), 'messages.1.content', '');

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => [
                            'content' => json_encode(['questions' => [
                                'For your target position of Developer, describe a technical issue you debugged.',
                            ]]),
                        ],
                    ]],
                ], 200);
            },
        ]);

        $dataset = QuestionDatasetProvider::find('ph_it_programming');

        $this->assertSame('2026-08-01', data_get($dataset, 'storage_question_bank.version'));

        $questions = AIService::generateQuestions(
            1,
            'Developer',
            'medium',
            'Philippines IT interview',
            'openai',
            datasetContext: $dataset
        );

        $this->assertSame(['For your target position of Developer, describe a technical issue you debugged.'], $questions);
        $this->assertStringContainsString('Reliable question-bank version: 2026-08-01', $capturedPrompt);
        $this->assertStringContainsString('PH IT and Programming', $capturedPrompt);
        $this->assertStringContainsString('Tell me about a web or software project where you balanced speed, quality, and maintainability.', $capturedPrompt);
    }

    public function test_user_ai_generated_start_question_is_saved_to_admin_question_bank(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Job Interview']);
        $questionText = 'Tell me about a production issue you diagnosed and resolved.';
        $roleAlignedQuestionText = 'For your target position of Backend Developer, tell me about a production issue you diagnosed and resolved.';
        $this->aiProvider('OpenAI', [
            'api_endpoint' => 'https://api.openai.com/v1',
            'is_primary' => true,
        ]);

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
            'ai_provider' => 'openai',
        ]);

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => null,
            'category_id' => $category->id,
            'question_text' => $roleAlignedQuestionText,
            'source_type' => 'ai_adapted_source_backed',
            'ai_provider' => 'openai',
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
        $this->assertStringContainsString('Reliable question-bank version: 2026-08-01', $capturedPrompt);
        $this->assertStringContainsString('PH IT and Programming', $capturedPrompt);

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'category_id' => $category->id,
            'question_text' => $roleAlignedFollowUpText,
            'ai_provider' => 'openai',
        ]);

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => null,
            'category_id' => $category->id,
            'question_text' => $roleAlignedFollowUpText,
            'source_type' => 'ai_adapted_source_backed',
            'ai_provider' => 'openai',
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

    public function test_interview_answer_saves_when_optional_coaching_analysis_fails(): void
    {
        $this->app->instance(EvidenceBasedCoachingService::class, new class
        {
            public function normalizeObservationData(?array $clientData, string $answerText, array $metrics, bool $cameraEnabled): array
            {
                throw new \RuntimeException('Coaching normalization unavailable.');
            }

            public function forAnswer(string $answerText, Question|array|null $question, array $metrics, array $observationData = []): array
            {
                throw new \RuntimeException('Coaching feedback unavailable.');
            }
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a release you owned.',
        ]);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I owned the release checklist, coordinated QA, and documented the final result before launch.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $savedAnswer = InterviewAnswer::where('interview_session_id', $session->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $this->assertSame(
            EvidenceBasedCoachingService::VERSION,
            data_get($savedAnswer->coaching_feedback, 'version')
        );
        $this->assertSame('local_fallback', data_get($savedAnswer->coaching_feedback, 'content_alignment.evaluation_source'));
        $this->assertSame('not_measured', data_get($savedAnswer->observation_data, 'delivery.status'));
        $this->assertSame('I owned the release checklist, coordinated QA, and documented the final result before launch.', $savedAnswer->answer_text);
    }

    public function test_interview_answer_repairs_missing_answer_columns_before_saving(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category(['title' => 'Software Engineering']);
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a release you owned.',
        ]);

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('interview_answers');
        Schema::create('interview_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->string('response_mode')->default('text');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();

        $this->assertFalse(Schema::hasColumn('interview_answers', 'retry_of_answer_id'));

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I owned the release checklist, coordinated QA, and documented the final result before launch.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue(Schema::hasColumn('interview_answers', 'retry_of_answer_id'));
        $this->assertTrue(Schema::hasColumn('interview_answers', 'voice_recording_path'));
        $this->assertTrue(Schema::hasColumn('interview_answers', 'voice_recording_mime_type'));
        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the release checklist, coordinated QA, and documented the final result before launch.',
        ]);
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

    public function test_abort_interview_marks_session_ended_preserves_answers_and_clears_active_keys(): void
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
            'answer_text' => 'Draft answer that should be preserved.',
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
            ->postJson(route('interview.abort'), [
                'session_id' => $session->id,
                'duration_seconds' => 95,
                'current_question_index' => 0,
                'question_id' => $question->id,
                'answer_text' => 'Final draft answer saved before ending.',
                'response_mode' => 'text',
                'elapsed_seconds' => 31,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'redirect_url' => route('user.review', $session->id),
            ])
            ->assertSessionMissing('active_interview_id')
            ->assertSessionMissing('active_interview_provider')
            ->assertSessionMissing('active_interview_context');

        $this->assertDatabaseHas('interview_sessions', [
            'id' => $session->id,
            'status' => 'ended',
            'score_eligible' => false,
            'duration_seconds' => 95,
            'current_question_index' => 0,
        ]);
        $this->assertDatabaseHas('questions', ['id' => $question->id]);
        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'Final draft answer saved before ending.',
        ]);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);

        $this->actingAs($user)
            ->get(route('user.review', $session->id))
            ->assertOk()
            ->assertSee('Ended Session Review')
            ->assertSee('No feedback is available for this session.')
            ->assertSee('Final draft answer saved before ending.');
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
            ->assertSee('requestAbortInterviewSession', false)
            ->assertSee('confirmAbortInterviewSession', false)
            ->assertSee(route('interview.abort'), false)
            ->assertSee('openingHasPlayed', false)
            ->assertDontSee('firstQuestionIntroText', false)
            ->assertDontSee('Here is your first question.', false)
            ->assertDontSee('first_question_intro', false)
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

    public function test_interview_finish_is_fast_idempotent_and_creates_complete_ai_feedback(): void
    {
        $this->fakeOpenAiFeedback();
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
        $this->assertSame('openai', $savedAnswer->ai_provider);
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
            ->assertSee('Answer Match')
            ->assertSee($question->question_text)
            ->assertSee('Good start')
            ->assertSee('Improve')
            ->assertSee('Next try checklist')
            ->assertSee('Done when')
            ->assertSee('Question next steps')
            ->assertSee('Question results')
            ->assertDontSee('â€œ', false);

        $session->update(['is_public' => true, 'share_token' => 'alignment-review-token']);
        $this->get(route('shared.review', 'alignment-review-token'))
            ->assertOk()
            ->assertSee('Answer Match')
            ->assertSee($question->question_text)
            ->assertSee('Good start')
            ->assertSee('Improve')
            ->assertSee('Next try checklist')
            ->assertSee('Done when')
            ->assertSee('Question next steps')
            ->assertSee('Question results')
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

    public function test_interview_finish_completes_with_fallback_when_report_coaching_analysis_fails(): void
    {
        $this->fakeOpenAiFeedback();
        $this->app->instance(EvidenceBasedCoachingService::class, new class
        {
            public function forAnswer(string $answerText, Question|array|null $question, array $metrics, array $observationData = []): array
            {
                throw new \RuntimeException('Report answer coaching unavailable.');
            }

            public function sessionSummary($answers): array
            {
                throw new \RuntimeException('Report summary unavailable.');
            }
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the support checklist, coordinated the update, and documented the final handoff result.',
            'response_mode' => 'text',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $savedAnswer = $answer->fresh();
        $feedback = Feedback::where('interview_session_id', $session->id)->firstOrFail();

        $this->assertSame('local_fallback', data_get($savedAnswer->coaching_feedback, 'content_alignment.evaluation_source'));
        $this->assertSame('limited', data_get($feedback->coaching_summary, 'feedback_quality.status'));
        $this->assertNotEmpty(data_get($feedback->coaching_summary, 'content_overview'));
        $this->assertNotEmpty(data_get($feedback->coaching_summary, 'question_improvements'));
    }

    public function test_interview_finish_completes_with_fallback_when_assessment_helpers_fail(): void
    {
        $this->fakeOpenAiFeedback();
        $this->app->instance(TrustworthyAssessmentService::class, new class extends TrustworthyAssessmentService
        {
            public function answerEvidence(string $answer, ?string $feedback = null, Question|array|null $question = null): array
            {
                throw new \RuntimeException('Evidence assessment unavailable.');
            }

            public function groundedRevisionTemplate(string $answer, ?array $evidence = null): string
            {
                throw new \RuntimeException('Revision template unavailable.');
            }

            public function sessionMetadata(InterviewSession $session, Collection $answers, array $metrics, int $starScore, int $jobEvidenceScore): array
            {
                throw new \RuntimeException('Session metadata unavailable.');
            }
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a process you improved.',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I reviewed the process, coordinated the update with my team, and documented the final handoff result.',
            'response_mode' => 'text',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $savedAnswer = $answer->fresh();
        $score = Score::where('interview_session_id', $session->id)->firstOrFail();

        $this->assertStringContainsString('I would answer:', $savedAnswer->better_sample_answer);
        $this->assertSame(
            'A dependable automatic answer check was unavailable for this retry.',
            data_get($savedAnswer->evidence_map, 'missing_evidence.0')
        );
        $this->assertTrue((bool) data_get($score->rubric, 'fallback_metadata'));
        $this->assertDatabaseHas('feedback', ['interview_session_id' => $session->id]);
    }

    public function test_stale_feedback_processing_state_can_recover_without_duplicate_records(): void
    {
        $this->fakeOpenAiFeedback();
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
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
    }

    public function test_interview_finish_repairs_missing_optional_session_columns_before_processing(): void
    {
        $this->fakeOpenAiFeedback();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the final checklist, coordinated the review, and confirmed the release result with my lead.',
            'response_mode' => 'text',
        ]);

        foreach (['action_plan', 'session_state', 'current_question_index', 'duration_seconds', 'notes'] as $column) {
            if (Schema::hasColumn('interview_sessions', $column)) {
                Schema::table('interview_sessions', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), [
                'session_id' => $session->id,
                'duration_seconds' => 75,
            ])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        foreach (['action_plan', 'session_state', 'current_question_index', 'duration_seconds', 'notes'] as $column) {
            $this->assertTrue(Schema::hasColumn('interview_sessions', $column), "Expected {$column} to be repaired.");
        }

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
    }

    public function test_interview_finish_does_not_complete_when_ai_feedback_generation_crashes(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I organized the handoff, clarified ownership, and verified the result with the receiving team.',
            'response_mode' => 'text',
        ]);

        $this->app->instance(InterviewController::class, new class extends InterviewController
        {
            protected function generateInterviewFeedbackForSession(
                InterviewSession $session,
                ?GameLevel $gameLevel,
                array $sessionData,
                array $answersData,
                ?string $feedbackProvider = null
            ): array {
                throw new \RuntimeException('Simulated provider crash.');
            }
        });

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(503)
            ->assertJsonPath('retry_after_ms', 1500);

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'in_progress']);
        $this->assertSame(0, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(0, Feedback::where('interview_session_id', $session->id)->count());
        $this->assertEmpty(InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail()->ai_feedback);
    }

    public function test_interview_finish_uses_local_feedback_when_six_ai_feedback_providers_fail(): void
    {
        foreach ([
            'HUGGINGFACE_API_KEY' => 'hf_test_token',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GROQ_API_KEY' => 'groq_test_token',
            'OPENROUTER_API_KEY' => 'openrouter_test_token',
            'COHERE_API_KEY' => 'cohere_test_token',
            'OPENAI_API_KEY' => 'openai_test_token',
            'ANTHROPIC_API_KEY' => '',
            'WISDOMGATE_API_KEY' => '',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'huggingface,gemini,groq,openrouter,cohere,openai',
            'AI_FEEDBACK_MAX_PROVIDERS' => '6',
            'AI_FEEDBACK_ATTEMPTS' => '1',
            'AI_FEEDBACK_HTTP_ATTEMPTS' => '1',
            'AI_FEEDBACK_DEADLINE_SECONDS' => '30',
        ] as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        Http::fake([
            '*' => Http::response(['error' => 'provider unavailable'], 500),
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a time you improved a support handoff.',
        ]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the support handoff, documented repeated issues, coordinated the update, and confirmed the final result with my supervisor.',
            'response_mode' => 'text',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'huggingface',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
        $savedAnswer = InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail();
        $this->assertNotEmpty($savedAnswer->ai_feedback);
        $this->assertSame('local_evidence', data_get($savedAnswer->coaching_feedback, 'content_alignment.evaluation_source'));
        Http::assertSentCount(6);
    }

    public function test_interview_finish_tolerates_missing_feedback_coaching_summary_column(): void
    {
        $this->fakeOpenAiFeedback();
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
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Answer Coaching Summary')
            ->assertSee('Question results')
            ->assertSee($question->question_text);
    }

    public function test_interview_finish_repairs_missing_report_tables(): void
    {
        $this->fakeOpenAiFeedback();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I owned the checklist, coordinated QA approval, and documented the final handoff result.',
            'response_mode' => 'text',
        ]);

        Schema::dropIfExists('scores');
        Schema::dropIfExists('feedback');

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
            ])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertTrue(Schema::hasTable('scores'));
        $this->assertTrue(Schema::hasTable('feedback'));
        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertDatabaseHas('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseHas('feedback', ['interview_session_id' => $session->id]);
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
            ->assertSee('Answer Coaching Summary')
            ->assertSee('Answer Match')
            ->assertSee('Question results')
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

    public function test_user_review_renders_saved_report_when_feedback_refresh_fails(): void
    {
        Log::spy();

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, [
            'status' => 'completed',
            'action_plan' => [
                'headline' => ['malformed'],
                'target_score' => ['bad'],
                'priorities' => [
                    ['skill' => 'Clarity', 'score' => 62, 'task' => 'Add a specific result.'],
                    'bad priority row',
                ],
                'recommended_paths' => [
                    ['label' => 'PH Mock Interview', 'url' => route('interview.setup')],
                    'bad path row',
                ],
                'next_session' => 'bad next session',
            ],
        ]);
        $question = $this->question($category, [
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a time you improved a process.',
        ]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I reviewed the process, coordinated the update, and confirmed the handoff improved.',
            'response_mode' => 'text',
            'ai_feedback' => 'Saved feedback remains visible.',
            'score' => 62,
        ]);

        $this->app->instance(InterviewController::class, new class extends InterviewController
        {
            public function ensureCompletedSessionFeedbackIsCurrent(InterviewSession $session, $gameLevel = null): bool
            {
                throw new \RuntimeException('Simulated refresh failure.');
            }
        });

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Detailed Feedback Report')
            ->assertSee('Saved feedback remains visible.')
            ->assertSee('Practice plan')
            ->assertSee('Clarity');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Detailed feedback refresh failed; rendering saved report data.'
                && (int) ($context['session_id'] ?? 0) === (int) $session->id
                && $context['error_type'] === \RuntimeException::class);
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
        $this->fakeOpenAiFeedback();
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
            ->withSession(['active_interview_provider' => 'openai'])
            ->postJson(route('interview.answer.retry', $answer), [
                'answer_text' => 'During a difficult project, I diagnosed the release issue, coordinated the rollback, and completed the recovery within ten minutes.',
                'response_mode' => 'text',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'coaching_feedback' => ['content_alignment'],
                'coaching_html',
            ]);
        $retry = InterviewAnswer::where('retry_of_answer_id', $answer->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(80, (int) $retry->scoring_confidence);
        $this->assertSame('candidate_facts', $retry->improved_answer_source);
        $this->assertSame($retry->id, data_get($retry->coaching_feedback, 'content_alignment.answer_id'));
        $this->assertSame($question->id, data_get($retry->coaching_feedback, 'content_alignment.question_id'));
        $this->assertNotEmpty(data_get($retry->coaching_feedback, 'content_alignment.evidence_quotes'));
        $this->assertStringContainsString('Answer Coaching', (string) $response->json('coaching_html'));
        $this->assertStringContainsString('Answer Match', (string) $response->json('coaching_html'));
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
            'weaknesses' => 'The original answer lacked a true result.',
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
            ->assertSee('Answer Coaching Summary')
            ->assertSee('Shared summary observation: both retries added relevant evidence.')
            ->assertSee('Shared summary action: keep the verified outcome in the final answer.')
            ->assertSee('Retry Tries')
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
        $this->fakeOpenAiFeedback();
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
            ->withSession(['active_interview_provider' => 'openai'])
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Score version '.TrustworthyAssessmentService::SCORE_VERSION);

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
            ->assertSee('No earlier scored session yet.')
            ->assertDontSee('Speaking Pace')
            ->assertDontSee('135 WPM')
            ->assertDontSee('STAR Framework Analysis');
        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertSee('feedback-report-meta', false)
            ->assertSee('feedback-hero-panel', false)
            ->assertSee('feedback-hero-grid', false)
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
                    'status_label' => 'Not enough detail',
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
            ->assertSee('Not enough detail')
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

    private function fakeOpenAiFeedback(array $answers = []): void
    {
        Http::fake([
            'api.openai.com/*' => function ($request) use ($answers) {
                $transcript = $answers !== []
                    ? $this->feedbackTranscriptFromAnswers($answers)
                    : $this->feedbackTranscriptFromPrompt((string) collect(data_get($request->data(), 'messages', []))
                        ->pluck('content')
                        ->filter()
                        ->implode("\n"));
                $items = array_map(fn (array $answer): array => $this->fakeFeedbackItem($answer), $transcript);
                $firstQuote = collect($items)
                    ->flatMap(fn (array $item): array => $item['evidence_quotes'] ?? [])
                    ->filter()
                    ->first();

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => [
                            'content' => json_encode([
                                'per_question_feedback' => $items,
                                'session_feedback' => [
                                    'strengths' => $firstQuote
                                        ? 'The AI review used saved answer details such as "'.$firstQuote.'" to identify what worked.'
                                        : 'The AI review found too little answer detail to name a clear strength.',
                                    'weaknesses' => 'Some responses need a clearer result or missing detail from the same saved answer.',
                                    'improvement_suggestions' => 'Keep each answer direct, remove repeated wording, and add the final result only when it is true.',
                                ],
                            ]),
                        ],
                    ]],
                ], 200);
            },
        ]);
    }

    private function feedbackTranscriptFromAnswers(array $answers): array
    {
        return array_values(array_map(function ($answer): array {
            if ($answer instanceof InterviewAnswer) {
                $answer->loadMissing('question');

                return [
                    'id' => $answer->id,
                    'question' => $answer->question->question_text ?? '',
                    'question_type' => $answer->question->type ?? null,
                    'expected_answer_guide' => $answer->question->expected_guide ?? null,
                    'mapped_skills' => $answer->question->mapped_skills ?? [],
                    'candidate_answer' => $answer->is_skipped ? '(Skipped or no answer)' : ($answer->answer_text ?? ''),
                    'star_applicable' => QuestionIntentService::starApplicable($answer->question),
                ];
            }

            return is_array($answer) ? $answer : [];
        }, $answers));
    }

    private function feedbackTranscriptFromPrompt(string $prompt): array
    {
        $marker = 'UNTRUSTED TRANSCRIPT DATA JSON:';
        $start = strpos($prompt, $marker);
        if ($start === false) {
            return [];
        }

        $afterMarker = substr($prompt, $start + strlen($marker));
        $jsonStart = strpos($afterMarker, '[');
        $jsonEnd = strpos($afterMarker, "\nProvide your evaluation STRICTLY", $jsonStart === false ? 0 : $jsonStart);
        if ($jsonStart === false || $jsonEnd === false || $jsonEnd <= $jsonStart) {
            return [];
        }

        $decoded = json_decode(substr($afterMarker, $jsonStart, $jsonEnd - $jsonStart), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function fakeFeedbackItem(array $answer): array
    {
        $answerText = trim((string) ($answer['candidate_answer'] ?? $answer['answer'] ?? ''));
        $questionText = trim((string) ($answer['question'] ?? $answer['question_text'] ?? ''));
        $isSkipped = $answerText === '' || strcasecmp($answerText, '(Skipped or no answer)') === 0;
        $wordCount = str_word_count($answerText);
        $isTooShort = ! $isSkipped && $wordCount < 10;
        $quote = $isSkipped ? '' : $this->feedbackEvidenceQuote($answerText);
        $starApplicable = array_key_exists('star_applicable', $answer)
            ? (bool) $answer['star_applicable']
            : QuestionIntentService::starApplicable([
                'question' => $questionText,
                'question_type' => $answer['question_type'] ?? null,
                'expected_guide' => $answer['expected_answer_guide'] ?? $answer['expected_guide'] ?? null,
                'mapped_skills' => $answer['mapped_skills'] ?? [],
            ]);
        $score = $isSkipped ? 0 : ($isTooShort ? 8 : 82);
        $specificTerms = $this->feedbackSpecificTerms($answerText, $questionText);
        $alignment = match (true) {
            $isSkipped => 'skipped',
            $isTooShort => 'insufficient_evidence',
            default => 'directly_addressed',
        };

        return [
            'id' => (int) ($answer['id'] ?? 0),
            'score' => $score,
            'clarity_score' => $score,
            'relevance_score' => $score,
            'grammar_score' => $score,
            'professionalism_score' => $score,
            'star_applicable' => $starApplicable,
            'star_method_score' => $starApplicable && ! $isSkipped && ! $isTooShort ? 75 : 0,
            'evidence_quotes' => $quote !== '' ? [$quote] : [],
            'question_focus' => $questionText,
            'answer_alignment' => $alignment,
            'missing_criteria' => [],
            'ai_feedback' => $isSkipped
                ? 'For "'.$questionText.'", this answer was skipped, so AI could not check saved answer evidence for this question.'
                : 'For "'.$questionText.'", you stated "'.$quote.'", which directly addressed this answer with specific saved details. The review is tied to '.$specificTerms.' from this answer.',
            'better_sample_answer' => $isSkipped ? '' : 'I would answer: '.$answerText,
            'follow_up_question' => 'What final result or detail from this answer would make it stronger?',
            'coaching' => [
                'keep' => $isSkipped
                    ? 'For "'.$questionText.'", there is no saved answer detail to keep yet.'
                    : 'Keep "'.$quote.'" as the saved detail for "'.$questionText.'".',
                'improve' => 'Add the missing '.$specificTerms.' result or detail for "'.$questionText.'".',
                'next_try' => 'Answer "'.$questionText.'" by connecting '.$specificTerms.' to one true result.',
                'next_attempt_steps' => $isSkipped
                    ? [
                        'Start with a direct answer to "'.$questionText.'".',
                        'Add one true detail about '.$specificTerms.'.',
                    ]
                    : [
                        'Start with a direct answer to "'.$questionText.'".',
                        'Use "'.$quote.'" as the support.',
                    ],
                'success_check' => 'The retry clearly links '.$specificTerms.' to "'.$questionText.'".',
            ],
        ];
    }

    private function feedbackSpecificTerms(string $answerText, string $questionText): string
    {
        preg_match_all('/[a-z][a-z0-9]+/i', $answerText.' '.$questionText, $matches);
        $stopWords = [
            'about', 'after', 'answer', 'before', 'could', 'detail', 'during', 'every',
            'final', 'from', 'question', 'result', 'same', 'that', 'their', 'this',
            'what', 'when', 'where', 'which', 'with', 'would', 'your',
        ];
        $terms = array_values(array_unique(array_filter(
            array_map('strtolower', $matches[0] ?? []),
            fn (string $term): bool => strlen($term) > 4 && ! in_array($term, $stopWords, true)
        )));

        return $terms === []
            ? 'the saved details'
            : implode(' and ', array_slice($terms, 0, 2));
    }

    private function feedbackEvidenceQuote(string $answerText): string
    {
        $clean = trim((string) preg_replace('/\s+/', ' ', $answerText));

        return mb_strlen($clean) > 260 ? mb_substr($clean, 0, 260) : $clean;
    }

    private function aiProvider(string $name, array $overrides = []): AiProvider
    {
        return AiProvider::create(array_merge([
            'name' => $name,
            'api_endpoint' => 'https://api.example.test/v1',
            'api_key' => Crypt::encryptString(strtolower(str_replace(' ', '-', $name)).'-test-key'),
            'status' => 'active',
            'is_primary' => false,
            'is_fallback' => false,
        ], $overrides));
    }

    private function setEnvValue(string $key, string $value): void
    {
        if (! array_key_exists($key, $this->originalEnvValues)) {
            $this->originalEnvValues[$key] = getenv($key);
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
