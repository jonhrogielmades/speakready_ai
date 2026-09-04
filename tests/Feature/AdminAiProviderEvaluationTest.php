<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\AiProviderEvaluationResult;
use App\Models\AiProviderEvaluationRun;
use App\Models\AiProviderLog;
use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\AiProviderEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiProviderEvaluationTest extends TestCase
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

    public function test_admin_can_view_ai_provider_evaluation_dashboard(): void
    {
        $this->clearProviderEnv();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $provider = $this->configuredProvider();
        $run = $this->evaluationRun($provider);

        AiProviderLog::create([
            'provider_id' => $provider->id,
            'module' => 'question_generation',
            'endpoint' => 'openai',
            'response_time_ms' => 900,
            'status' => 'success',
        ]);
        AiProviderLog::create([
            'provider_id' => $provider->id,
            'module' => 'feedback_generation',
            'endpoint' => 'openai',
            'response_time_ms' => 1200,
            'status' => 'failed',
            'error_message' => 'Timeout',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ai.evaluation', ['run' => $run->id]))
            ->assertOk()
            ->assertSee('AI Provider Evaluation')
            ->assertSee('Provider Evidence Matrix')
            ->assertSee('Interview Questions Use')
            ->assertSee('Interview Feedback Uses')
            ->assertSee('OpenAI')
            ->assertSee('Panelist requirement: evaluate 3 or more AI APIs')
            ->assertSee('1/3 active APIs')
            ->assertSee('Compare All Providers')
            ->assertDontSee('Run Benchmark')
            ->assertSee('Excel CSV')
            ->assertSee('Clear All')
            ->assertDontSee('PDF Report')
            ->assertSee('Human Audit Evidence')
            ->assertSee('Ranked User-Requested Generated Questions and Feedback by AI Provider')
            ->assertSee('8 providers')
            ->assertSee('0 with output')
            ->assertSee('No user-requested output yet')
            ->assertDontSee('How would you handle a customer complaint during a live service call?')
            ->assertDontSee('For Tell me about a time you solved a customer issue.');
    }

    public function test_admin_can_export_ai_provider_evaluation_as_excel_csv(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $provider = $this->configuredProvider();
        $run = $this->evaluationRun($provider);

        $response = $this->actingAs($admin)
            ->get(route('admin.ai.evaluation.export', ['run' => $run->id]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Provider,', $content);
        $this->assertStringContainsString('Overall Evidence Score', $content);
        $this->assertStringContainsString('User Evidence Rank', $content);
        $this->assertStringContainsString('Best User Evidence Score', $content);
        $this->assertStringContainsString('Row Type', $content);
        $this->assertStringContainsString('Generated Question', $content);
        $this->assertStringContainsString('Generated Feedback', $content);
        $this->assertStringNotContainsString('Provider Summary', $content);
        $this->assertStringNotContainsString('How would you handle a customer complaint during a live service call?', $content);
        $this->assertStringNotContainsString('For Tell me about a time you solved a customer issue.', $content);
    }

    public function test_admin_can_open_printable_ai_provider_evaluation_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $provider = $this->configuredProvider();
        $run = $this->evaluationRun($provider);

        $this->actingAs($admin)
            ->get(route('admin.ai.evaluation.report', ['run' => $run->id]))
            ->assertOk()
            ->assertSee('AI Provider Evidence Report')
            ->assertSee('SpeakReady AI Provider Evaluation Evidence')
            ->assertSee('Interview question provider')
            ->assertSee('Interview feedback provider')
            ->assertSee('Ranked User-Requested Generated Outputs by AI Provider')
            ->assertDontSee('How would you handle a customer complaint during a live service call?')
            ->assertDontSee('For Tell me about a time you solved a customer issue.')
            ->assertSee('window.print()', false);
    }

    public function test_admin_evaluation_detects_production_questions_and_feedback_by_provider(): void
    {
        $this->clearProviderEnv();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $this->configuredProvider();
        $this->seedOlderUnrelatedProductionProviderOutputs();
        $this->seedProductionProviderOutputs();

        $this->actingAs($admin)
            ->get(route('admin.ai.evaluation'))
            ->assertOk()
            ->assertSee('Ranked User-Requested Generated Questions and Feedback by AI Provider')
            ->assertSee('8 providers')
            ->assertSee('1 with output')
            ->assertSee('Rank #1')
            ->assertSee('Best 82%')
            ->assertSee('View Best Questions')
            ->assertSee('View Best Feedback')
            ->assertSee('Best Generated Questions')
            ->assertSee('Best Generated Feedback')
            ->assertSee('Production question from OpenAI for a customer complaint scenario?')
            ->assertSee('User interview')
            ->assertSee('Production feedback from OpenAI cites the customer complaint answer.')
            ->assertSee('User feedback')
            ->assertSee('Based on user request: Session #')
            ->assertDontSee('Older unrelated question from OpenAI for another request?')
            ->assertDontSee('Older unrelated feedback from OpenAI should not appear.');

        $csv = $this->actingAs($admin)
            ->get(route('admin.ai.evaluation.export'))
            ->streamedContent();
        $this->assertStringContainsString('Generated Question', $csv);
        $this->assertStringContainsString('Generated Feedback', $csv);
        $this->assertStringContainsString('Rank #1', $csv);
        $this->assertStringContainsString('82%', $csv);
        $this->assertStringContainsString('User interview', $csv);
        $this->assertStringContainsString('User feedback', $csv);
        $this->assertStringContainsString('Production question from OpenAI for a customer complaint scenario?', $csv);
        $this->assertStringContainsString('Production feedback from OpenAI cites the customer complaint answer.', $csv);
        $this->assertStringNotContainsString('Older unrelated question from OpenAI for another request?', $csv);
        $this->assertStringNotContainsString('Older unrelated feedback from OpenAI should not appear.', $csv);

        $this->actingAs($admin)
            ->get(route('admin.ai.evaluation.report'))
            ->assertOk()
            ->assertSee('Ranked User-Requested Generated Outputs by AI Provider')
            ->assertSee('8 providers shown')
            ->assertSee('1 with generated user-request output')
            ->assertSee('Production question from OpenAI for a customer complaint scenario?')
            ->assertSee('Production feedback from OpenAI cites the customer complaint answer.')
            ->assertDontSee('Older unrelated question from OpenAI for another request?')
            ->assertDontSee('Older unrelated feedback from OpenAI should not appear.');
    }

    public function test_admin_can_clear_ai_provider_evaluation_evidence(): void
    {
        $this->clearProviderEnv();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $provider = $this->configuredProvider();
        $this->evaluationRun($provider);
        $this->seedProductionProviderOutputs();

        $question = Question::whereNotNull('ai_provider')->firstOrFail();
        $answer = InterviewAnswer::whereNotNull('ai_provider')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.ai.evaluation.clear'))
            ->assertRedirect(route('admin.ai.evaluation'))
            ->assertSessionHas('message');

        $this->assertDatabaseCount('ai_provider_evaluation_runs', 0);
        $this->assertDatabaseCount('ai_provider_evaluation_results', 0);
        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Production question from OpenAI for a customer complaint scenario?',
            'ai_provider' => null,
        ]);
        $this->assertDatabaseHas('interview_answers', [
            'id' => $answer->id,
            'ai_feedback' => 'Production feedback from OpenAI cites the customer complaint answer.',
            'ai_provider' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ai.evaluation'))
            ->assertOk()
            ->assertSee('8 providers')
            ->assertSee('0 with output')
            ->assertSee('No user-requested output yet')
            ->assertDontSee('Production question from OpenAI for a customer complaint scenario?')
            ->assertDontSee('Production feedback from OpenAI cites the customer complaint answer.');

        $csv = $this->actingAs($admin)
            ->get(route('admin.ai.evaluation.export'))
            ->streamedContent();

        $this->assertStringNotContainsString('Production question from OpenAI for a customer complaint scenario?', $csv);
        $this->assertStringNotContainsString('Production feedback from OpenAI cites the customer complaint answer.', $csv);
    }

    public function test_admin_can_poll_realtime_ai_provider_evaluation(): void
    {
        $this->clearProviderEnv();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $this->configuredProvider();
        $this->seedProductionProviderOutputs();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.ai.evaluation.realtime'));

        $response
            ->assertOk()
            ->assertJsonPath('poll_ms', 8000)
            ->assertJsonPath('total_provider_count', 8)
            ->assertJsonPath('active_configured_provider_count', 1)
            ->assertJsonPath('minimum_required_provider_count', 3)
            ->assertJsonPath('panelist_requirement_met', false)
            ->assertJsonPath('generated_provider_count', 1)
            ->assertJsonPath('generated_question_count', 1)
            ->assertJsonPath('generated_feedback_count', 1);

        $html = (string) $response->json('html');
        $this->assertStringContainsString('Ranked User-Requested Generated Questions and Feedback by AI Provider', $html);
        $this->assertStringContainsString('Rank #1', $html);
        $this->assertStringContainsString('View Best Questions', $html);
        $this->assertStringContainsString('View Best Feedback', $html);
        $this->assertStringContainsString('Production question from OpenAI for a customer complaint scenario?', $html);
        $this->assertStringContainsString('Production feedback from OpenAI cites the customer complaint answer.', $html);
    }

    public function test_admin_can_run_empty_ai_provider_comparison_without_external_calls(): void
    {
        $this->clearProviderEnv();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.ai.evaluation.run'))
            ->assertRedirect();

        $this->assertDatabaseHas('ai_provider_evaluation_runs', [
            'benchmark_version' => 'user-request-provider-comparison-v1',
            'status' => 'completed',
            'provider_count' => 0,
            'case_count' => 0,
        ]);
    }

    public function test_admin_comparison_requires_three_or_more_active_ai_apis(): void
    {
        $this->clearProviderEnv();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $this->configuredProvider('OpenAI', true);
        $this->configuredProvider('Groq', false);
        $this->seedProductionProviderOutputs();

        Http::fake();

        $this->actingAs($admin)
            ->post(route('admin.ai.evaluation.run'))
            ->assertRedirect()
            ->assertSessionHas('message');

        $run = AiProviderEvaluationRun::firstOrFail();
        $this->assertSame('user-request-provider-comparison-v1', $run->benchmark_version);
        $this->assertSame(2, $run->provider_count);
        $this->assertSame(0, $run->case_count);
        $this->assertStringContainsString('at least 3 active configured AI APIs', $run->summary['note'] ?? '');
        $this->assertDatabaseCount('ai_provider_evaluation_results', 0);
    }

    public function test_admin_can_run_ai_provider_comparison_for_each_configured_provider(): void
    {
        $this->clearProviderEnv();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $this->configuredProvider('OpenAI', true);
        $this->configuredProvider('Groq', false);
        $this->configuredProvider('OpenRouter', false);
        $this->seedProductionProviderOutputs();
        $answer = InterviewAnswer::with('question')->latest('id')->firstOrFail();

        Http::fake(function ($request) use ($answer) {
            $prompt = (string) data_get($request->data(), 'messages.1.content', '');
            $url = (string) $request->url();
            $providerLabel = match (true) {
                str_contains($url, 'groq') => 'Groq',
                str_contains($url, 'openrouter') => 'OpenRouter',
                default => 'OpenAI',
            };

            if (str_contains($prompt, 'mock interview questions')) {
                $questions = $providerLabel === 'OpenAI'
                    ? [
                        'How would you handle a customer complaint as a Customer Service Representative?',
                        'How would you explain an account delay to a customer during a service call?',
                        'What follow-up steps would you take after checking a customer account?',
                    ]
                    : [
                        'What motivates you in this role?',
                        'How do you prepare for a busy work day?',
                        'Tell me about a challenge you handled?',
                    ];

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => [
                            'content' => json_encode([
                                'questions' => $questions,
                            ]),
                        ],
                    ]],
                ], 200);
            }

            return Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => [
                        'content' => json_encode($this->validFeedbackBenchmarkResponse(
                            $providerLabel,
                            $answer->id,
                            $answer->question?->question_text,
                            strtolower((string) $answer->question?->type) === 'behavioral'
                        )),
                    ],
                ]],
            ], 200);
        });

        $this->actingAs($admin)
            ->post(route('admin.ai.evaluation.run'))
            ->assertRedirect();

        $this->assertDatabaseHas('ai_provider_evaluation_runs', [
            'benchmark_version' => 'user-request-provider-comparison-v1',
            'status' => 'completed',
            'provider_count' => 3,
            'case_count' => 6,
        ]);
        $this->assertDatabaseHas('ai_provider_evaluation_results', [
            'provider_key' => 'openai',
            'task_type' => 'question_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_evaluation_results', [
            'provider_key' => 'openai',
            'task_type' => 'feedback_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_evaluation_results', [
            'provider_key' => 'groq',
            'task_type' => 'question_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_evaluation_results', [
            'provider_key' => 'groq',
            'task_type' => 'feedback_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_evaluation_results', [
            'provider_key' => 'openrouter',
            'task_type' => 'question_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_evaluation_results', [
            'provider_key' => 'openrouter',
            'task_type' => 'feedback_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_logs', [
            'endpoint' => 'openai',
            'module' => 'question_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_logs', [
            'endpoint' => 'openai',
            'module' => 'feedback_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_logs', [
            'endpoint' => 'groq',
            'module' => 'question_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_logs', [
            'endpoint' => 'groq',
            'module' => 'feedback_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_logs', [
            'endpoint' => 'openrouter',
            'module' => 'question_generation',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('ai_provider_logs', [
            'endpoint' => 'openrouter',
            'module' => 'feedback_generation',
            'status' => 'success',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ai.evaluation'))
            ->assertOk()
            ->assertSee('Panelist requirement met: 3 active AI APIs can be evaluated.')
            ->assertSee('3/3 active APIs')
            ->assertSee('Ranked User-Requested Generated Questions and Feedback by AI Provider')
            ->assertSee('3 with output')
            ->assertSee('How would you handle a customer complaint as a Customer Service Representative?')
            ->assertSee('OpenAI says the answer')
            ->assertSee('Groq says the answer')
            ->assertSee('OpenRouter says the answer')
            ->assertSee('User request comparison')
            ->assertSee('Rank #1')
            ->assertSee('Rank #2')
            ->assertSee('Rank #3');
    }

    public function test_interview_provider_selector_uses_highest_ranked_question_and_feedback_results(): void
    {
        $this->clearProviderEnv();
        $openAi = $this->configuredProvider('OpenAI', true);
        $groq = $this->configuredProvider('Groq', false);
        $openRouter = $this->configuredProvider('OpenRouter', false);

        $run = AiProviderEvaluationRun::create([
            'benchmark_version' => AiProviderEvaluationService::USER_REQUEST_COMPARISON_VERSION,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'provider_count' => 3,
            'case_count' => 6,
            'summary' => [
                'best_provider' => 'OpenRouter',
                'best_provider_score' => 97,
            ],
        ]);

        foreach ([
            [$openAi, 'openai', 'OpenAI', 'question_generation', 78, 76, 100, 900],
            [$groq, 'groq', 'Groq', 'question_generation', 94, 93, 100, 1200],
            [$openRouter, 'openrouter', 'OpenRouter', 'question_generation', 86, 91, 100, 700],
            [$openAi, 'openai', 'OpenAI', 'feedback_generation', 80, 78, 100, 850],
            [$groq, 'groq', 'Groq', 'feedback_generation', 86, 84, 100, 800],
            [$openRouter, 'openrouter', 'OpenRouter', 'feedback_generation', 97, 96, 100, 1400],
        ] as [$provider, $providerKey, $providerName, $taskType, $quality, $accuracy, $schema, $latency]) {
            AiProviderEvaluationResult::create([
                'run_id' => $run->id,
                'provider_id' => $provider->id,
                'provider_key' => $providerKey,
                'provider_name' => $providerName,
                'task_type' => $taskType,
                'case_key' => "user_request_{$taskType}_session_1",
                'status' => 'success',
                'response_time_ms' => $latency,
                'quality_score' => $quality,
                'reliability_score' => 95,
                'schema_score' => $schema,
                'accuracy_score' => $accuracy,
                'safety_score' => 100,
                'output_excerpt' => '{}',
                'evidence' => ['warnings' => []],
            ]);
        }

        $selector = app(AiProviderEvaluationService::class);

        $this->assertSame('groq', $selector->bestProviderKeyForInterviewTask('question_generation'));
        $this->assertSame('openrouter', $selector->bestProviderKeyForInterviewTask('feedback_generation'));
    }

    public function test_interview_provider_selector_penalizes_inconsistent_evaluation_results(): void
    {
        $this->clearProviderEnv();
        $openAi = $this->configuredProvider('OpenAI', true);
        $groq = $this->configuredProvider('Groq', false);
        $openRouter = $this->configuredProvider('OpenRouter', false);

        foreach ([1, 2, 3] as $runNumber) {
            $run = AiProviderEvaluationRun::create([
                'benchmark_version' => AiProviderEvaluationService::USER_REQUEST_COMPARISON_VERSION,
                'status' => 'completed',
                'started_at' => now()->subMinutes(10 - $runNumber),
                'completed_at' => now()->subMinutes(10 - $runNumber),
                'provider_count' => 3,
                'case_count' => 3,
            ]);

            AiProviderEvaluationResult::create([
                'run_id' => $run->id,
                'provider_id' => $openAi->id,
                'provider_key' => 'openai',
                'provider_name' => 'OpenAI',
                'task_type' => 'question_generation',
                'case_key' => "user_request_questions_session_{$runNumber}",
                'status' => $runNumber === 1 ? 'success' : 'failed',
                'response_time_ms' => $runNumber === 1 ? 700 : 12000,
                'quality_score' => $runNumber === 1 ? 99 : 0,
                'reliability_score' => $runNumber === 1 ? 100 : 0,
                'schema_score' => $runNumber === 1 ? 100 : 0,
                'accuracy_score' => $runNumber === 1 ? 99 : 0,
                'safety_score' => $runNumber === 1 ? 100 : 0,
                'output_excerpt' => '{}',
                'evidence' => ['warnings' => $runNumber === 1 ? [] : ['Provider failed.']],
            ]);

            foreach ([[$groq, 'groq', 'Groq', 88], [$openRouter, 'openrouter', 'OpenRouter', 82]] as [$provider, $key, $name, $score]) {
                AiProviderEvaluationResult::create([
                    'run_id' => $run->id,
                    'provider_id' => $provider->id,
                    'provider_key' => $key,
                    'provider_name' => $name,
                    'task_type' => 'question_generation',
                    'case_key' => "user_request_questions_session_{$runNumber}",
                    'status' => 'success',
                    'response_time_ms' => 900,
                    'quality_score' => $score,
                    'reliability_score' => 100,
                    'schema_score' => 100,
                    'accuracy_score' => $score,
                    'safety_score' => 100,
                    'output_excerpt' => '{}',
                    'evidence' => ['warnings' => []],
                ]);
            }
        }

        $this->assertSame('groq', app(AiProviderEvaluationService::class)->bestProviderKeyForInterviewTask('question_generation'));
    }

    public function test_interview_provider_selector_uses_live_log_reliability_when_scores_are_close(): void
    {
        $this->clearProviderEnv();
        $openAi = $this->configuredProvider('OpenAI', true);
        $groq = $this->configuredProvider('Groq', false);
        $openRouter = $this->configuredProvider('OpenRouter', false);
        $run = AiProviderEvaluationRun::create([
            'benchmark_version' => AiProviderEvaluationService::USER_REQUEST_COMPARISON_VERSION,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'provider_count' => 3,
            'case_count' => 3,
        ]);

        foreach ([
            [$openAi, 'openai', 'OpenAI', 87, 87],
            [$groq, 'groq', 'Groq', 92, 92],
            [$openRouter, 'openrouter', 'OpenRouter', 94, 94],
        ] as [$provider, $key, $name, $quality, $accuracy]) {
            AiProviderEvaluationResult::create([
                'run_id' => $run->id,
                'provider_id' => $provider->id,
                'provider_key' => $key,
                'provider_name' => $name,
                'task_type' => 'feedback_generation',
                'case_key' => 'user_request_feedback_session_1',
                'status' => 'success',
                'response_time_ms' => 900,
                'quality_score' => $quality,
                'reliability_score' => 95,
                'schema_score' => 100,
                'accuracy_score' => $accuracy,
                'safety_score' => 100,
                'output_excerpt' => '{}',
                'evidence' => ['warnings' => []],
            ]);
        }

        foreach (range(1, 5) as $index) {
            AiProviderLog::create([
                'provider_id' => $groq->id,
                'module' => 'feedback_generation',
                'endpoint' => 'groq',
                'response_time_ms' => 850,
                'status' => 'success',
            ]);

            AiProviderLog::create([
                'provider_id' => $openRouter->id,
                'module' => 'feedback_generation',
                'endpoint' => 'openrouter',
                'response_time_ms' => $index === 1 ? 900 : 12000,
                'status' => $index === 1 ? 'success' : 'failed',
            ]);
        }

        $this->assertSame('groq', app(AiProviderEvaluationService::class)->bestProviderKeyForInterviewTask('feedback_generation'));
    }

    public function test_interview_start_uses_ranked_question_and_feedback_providers(): void
    {
        $this->clearProviderEnv();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Job Interview',
            'type' => 'core',
            'description' => 'Job interview practice.',
            'status' => 'active',
        ]);

        $openAi = $this->configuredProvider('OpenAI', true);
        $groq = $this->configuredProvider('Groq', false);
        $openRouter = $this->configuredProvider('OpenRouter', false);
        $this->rankedEvaluationRun($openAi, $groq, $openRouter);

        $requestedUrls = [];
        Http::fake(function ($request) use (&$requestedUrls) {
            $requestedUrls[] = (string) $request->url();

            return Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => [
                        'content' => json_encode([
                            'questions' => [
                                'As a Developer, how would you debug a production issue while keeping stakeholders updated?',
                            ],
                        ]),
                    ],
                ]],
            ], 200);
        });

        $this->actingAs($user)
            ->post(route('interview.start'), [
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => 'Developer',
                'num_questions' => 5,
                'response_mode' => 'text',
                'time_limit' => 0,
            ])
            ->assertRedirect(route('interview.session'))
            ->assertSessionHas('active_interview_provider', 'groq')
            ->assertSessionHas('active_interview_feedback_provider', 'openrouter');

        $session = InterviewSession::where('user_id', $user->id)->firstOrFail();
        $generatedQuestion = Question::where('interview_session_id', $session->id)
            ->where('source_type', 'ai_adapted_source_backed')
            ->firstOrFail();

        $this->assertSame('groq', $generatedQuestion->ai_provider);
        $this->assertSame(
            'As a Developer, how would you debug a production issue while keeping stakeholders updated?',
            $generatedQuestion->question_text
        );
        $this->assertTrue(collect($requestedUrls)->contains(fn (string $url): bool => str_contains($url, 'groq')));
    }

    public function test_interview_finish_uses_ranked_feedback_provider(): void
    {
        $this->clearProviderEnv();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Job Interview',
            'type' => 'core',
            'description' => 'Job interview practice.',
            'status' => 'active',
        ]);
        $openAi = $this->configuredProvider('OpenAI', true);
        $groq = $this->configuredProvider('Groq', false);
        $openRouter = $this->configuredProvider('OpenRouter', false);
        $this->rankedEvaluationRun($openAi, $groq, $openRouter);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'in_progress',
        ]);
        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a difficult project.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'During a release issue, I checked the logs, coordinated the rollback, and restored service within ten minutes.',
            'response_mode' => 'text',
        ]);

        $requestedUrls = [];
        Http::fake(function ($request) use (&$requestedUrls, $answer, $question) {
            $requestedUrls[] = (string) $request->url();

            return Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => [
                        'content' => json_encode($this->validFeedbackBenchmarkResponse(
                            'OpenRouter',
                            $answer->id,
                            $question->question_text,
                            true
                        )),
                    ],
                ]],
            ], 200);
        });

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'groq',
            ])
            ->postJson(route('interview.finish'), [
                'session_id' => $session->id,
                'duration_seconds' => 90,
            ])
            ->assertOk();

        $this->assertSame('openrouter', $answer->fresh()->ai_provider);
        $this->assertTrue(collect($requestedUrls)->contains(fn (string $url): bool => str_contains($url, 'openrouter')));
    }

    private function rankedEvaluationRun(AiProvider $openAi, AiProvider $groq, AiProvider $openRouter): AiProviderEvaluationRun
    {
        $run = AiProviderEvaluationRun::create([
            'benchmark_version' => AiProviderEvaluationService::USER_REQUEST_COMPARISON_VERSION,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'provider_count' => 3,
            'case_count' => 6,
            'summary' => [
                'best_provider' => 'OpenRouter',
                'best_provider_score' => 97,
            ],
        ]);

        foreach ([
            [$openAi, 'openai', 'OpenAI', 'question_generation', 78, 76, 100, 900],
            [$groq, 'groq', 'Groq', 'question_generation', 94, 93, 100, 1200],
            [$openRouter, 'openrouter', 'OpenRouter', 'question_generation', 86, 91, 100, 700],
            [$openAi, 'openai', 'OpenAI', 'feedback_generation', 80, 78, 100, 850],
            [$groq, 'groq', 'Groq', 'feedback_generation', 86, 84, 100, 800],
            [$openRouter, 'openrouter', 'OpenRouter', 'feedback_generation', 97, 96, 100, 1400],
        ] as [$provider, $providerKey, $providerName, $taskType, $quality, $accuracy, $schema, $latency]) {
            AiProviderEvaluationResult::create([
                'run_id' => $run->id,
                'provider_id' => $provider->id,
                'provider_key' => $providerKey,
                'provider_name' => $providerName,
                'task_type' => $taskType,
                'case_key' => $taskType === 'question_generation'
                    ? 'user_request_questions_session_1'
                    : 'user_request_feedback_session_1',
                'status' => 'success',
                'response_time_ms' => $latency,
                'quality_score' => $quality,
                'reliability_score' => 95,
                'schema_score' => $schema,
                'accuracy_score' => $accuracy,
                'safety_score' => 100,
                'output_excerpt' => '{}',
                'evidence' => ['warnings' => []],
            ]);
        }

        return $run->fresh(['results']);
    }

    private function configuredProvider(string $name = 'OpenAI', bool $primary = true): AiProvider
    {
        return AiProvider::create([
            'name' => $name,
            'api_endpoint' => match (strtolower($name)) {
                'groq' => 'https://api.groq.com/openai/v1',
                'openrouter' => 'https://openrouter.ai/api/v1',
                default => 'https://api.openai.com/v1',
            },
            'api_key' => Crypt::encryptString('test_'.strtolower($name).'_key'),
            'status' => 'active',
            'is_primary' => $primary,
        ]);
    }

    private function evaluationRun(AiProvider $provider): AiProviderEvaluationRun
    {
        $run = AiProviderEvaluationRun::create([
            'benchmark_version' => 'panelist-evidence-v1',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'provider_count' => 1,
            'case_count' => 2,
            'summary' => [
                'best_provider' => 'OpenAI',
                'average_quality_score' => 88,
            ],
        ]);

        AiProviderEvaluationResult::create([
            'run_id' => $run->id,
            'provider_id' => $provider->id,
            'provider_key' => 'openai',
            'provider_name' => 'OpenAI',
            'task_type' => 'question_generation',
            'case_key' => 'question_generation_case',
            'status' => 'success',
            'response_time_ms' => 900,
            'quality_score' => 88,
            'reliability_score' => 100,
            'schema_score' => 100,
            'accuracy_score' => 84,
            'safety_score' => 100,
            'output_excerpt' => json_encode([
                'questions' => [
                    'How would you handle a customer complaint during a live service call?',
                    'What steps would you take if a customer issue remains unresolved?',
                ],
            ]),
            'evidence' => [
                'warnings' => [],
                'generated_questions' => [
                    'How would you handle a customer complaint during a live service call?',
                    'What steps would you take if a customer issue remains unresolved?',
                ],
            ],
        ]);

        $feedback = $this->validFeedbackBenchmarkResponse();

        AiProviderEvaluationResult::create([
            'run_id' => $run->id,
            'provider_id' => $provider->id,
            'provider_key' => 'openai',
            'provider_name' => 'OpenAI',
            'task_type' => 'feedback_generation',
            'case_key' => 'feedback_generation_case',
            'status' => 'success',
            'response_time_ms' => 900,
            'quality_score' => 88,
            'reliability_score' => 100,
            'schema_score' => 100,
            'accuracy_score' => 84,
            'safety_score' => 100,
            'output_excerpt' => json_encode($feedback),
            'evidence' => [
                'warnings' => [],
                'generated_feedback' => $feedback['per_question_feedback'],
            ],
        ]);

        return $run->fresh(['results']);
    }

    private function seedProductionProviderOutputs(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Customer Service',
            'type' => 'core',
            'description' => 'Customer support interview practice.',
            'status' => 'active',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Customer Service Representative',
            'num_questions' => 3,
            'interview_focus' => 'Customer complaint handling and account checking',
            'company_persona' => 'Customer support panelist',
            'question_types' => 'situational,behavioral',
            'ai_assistance_level' => 'standard',
            'interviewer_strictness' => 'neutral',
            'interview_format' => 'standard',
            'status' => 'completed',
        ]);
        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Production question from OpenAI for a customer complaint scenario?',
            'difficulty' => 'medium',
            'type' => 'Situational',
            'status' => 'active',
            'source_type' => 'ai_adapted_source_backed',
            'ai_provider' => 'openai',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I listened to the customer, checked the account, explained the delay, and confirmed the next step.',
            'ai_feedback' => 'Production feedback from OpenAI cites the customer complaint answer.',
            'better_sample_answer' => 'I listened to the customer, checked the account, and explained the next step.',
            'follow_up_question' => 'What result did your next step create for the customer?',
            'score' => 82,
            'clarity_score' => 80,
            'relevance_score' => 84,
            'grammar_score' => 82,
            'evidence_map' => [
                'supporting_excerpts' => ['I listened to the customer and checked the account.'],
            ],
            'ai_provider' => 'openai',
        ]);
    }

    private function seedOlderUnrelatedProductionProviderOutputs(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Sales',
            'type' => 'core',
            'description' => 'Sales interview practice.',
            'status' => 'active',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'hard',
            'target_position' => 'Sales Associate',
            'num_questions' => 3,
            'interview_focus' => 'Retail sales objection handling',
            'status' => 'completed',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Older unrelated question from OpenAI for another request?',
            'difficulty' => 'hard',
            'type' => 'Situational',
            'status' => 'active',
            'source_type' => 'ai_adapted_source_backed',
            'ai_provider' => 'openai',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I asked questions and handled the concern.',
            'ai_feedback' => 'Older unrelated feedback from OpenAI should not appear.',
            'better_sample_answer' => 'I asked questions and handled the concern.',
            'follow_up_question' => 'What happened after you handled the concern?',
            'score' => 90,
            'ai_provider' => 'openai',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }

    private function validFeedbackBenchmarkResponse(
        string $providerLabel = 'OpenAI',
        int $answerId = 1,
        ?string $questionFocus = null,
        bool $starApplicable = true
    ): array
    {
        $questionFocus ??= 'Tell me about a time you solved a customer issue.';
        $starScore = $starApplicable ? 100 : 0;

        return [
            'per_question_feedback' => [
                [
                    'id' => $answerId,
                    'score' => 88,
                    'clarity_score' => 86,
                    'relevance_score' => 90,
                    'grammar_score' => 90,
                    'professionalism_score' => 88,
                    'star_applicable' => $starApplicable,
                    'star_method_score' => $starScore,
                    'evidence_quotes' => [
                        'I listened to the customer',
                    ],
                    'question_focus' => $questionFocus,
                    'answer_alignment' => 'directly_addressed',
                    'missing_criteria' => [],
                    'ai_feedback' => "For {$questionFocus}, {$providerLabel} says the answer directly addressed the question because it used \"I listened to the customer\" and checked the account. It can add the final customer result.",
                    'better_sample_answer' => 'I listened to the customer, checked the account, explained the delay, and confirmed the next step.',
                    'follow_up_question' => 'What final customer result came after you confirmed the next step?',
                    'coaching' => [
                        'keep' => 'Keep the part where you listened to the customer and checked the account.',
                        'improve' => 'Add the final customer result after you confirmed the next step.',
                        'next_try' => 'Answer this customer question by naming the issue, your action, and the result.',
                        'next_attempt_steps' => [
                            'Name the customer complaint or delay.',
                            'Say that you listened and checked the account.',
                            'End with the customer result after the next step.',
                        ],
                        'success_check' => 'The retry answers the customer question with action and result.',
                    ],
                ],
            ],
            'session_feedback' => [
                'strengths' => 'The answer mentioned listening to the customer and checking the account.',
                'weaknesses' => 'The answer needs the final customer result after the next step.',
                'improvement_suggestions' => 'Add the customer outcome after explaining the delay.',
            ],
        ];
    }

    private function clearProviderEnv(): void
    {
        foreach ([
            'OPENAI_API_KEY',
            'GEMINI_API_KEY',
            'GROQ_API_KEY',
            'OPENROUTER_API_KEY',
            'COHERE_API_KEY',
            'ANTHROPIC_API_KEY',
            'WISDOMGATE_API_KEY',
            'HUGGINGFACE_API_KEY',
            'HF_TOKEN',
        ] as $key) {
            if (! array_key_exists($key, $this->originalEnvValues)) {
                $this->originalEnvValues[$key] = getenv($key);
            }

            putenv("{$key}=");
            $_ENV[$key] = '';
            $_SERVER[$key] = '';
        }
    }
}
