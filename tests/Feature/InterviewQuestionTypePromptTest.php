<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InterviewQuestionTypePromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_single_type_uses_a_matching_generation_example_even_with_a_resume(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $prompts = [];
        Http::fake([
            'api.openai.com/*' => function ($request) use (&$prompts) {
                $prompts[] = (string) data_get($request->data(), 'messages.1.content', '');

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => '{"questions":["What would you do next for this role?"]}'],
                    ]],
                ]);
            },
        ]);

        $examples = [
            'Behavioral' => 'Tell me about a time you used a skill relevant to the Developer role.',
            'Situational' => 'If an urgent priority changed during a Developer task, how would you respond?',
            'Technical' => 'How would you check that your work as a Developer meets the required standard?',
            'Personal' => 'What motivates you to pursue the Developer role?',
        ];

        foreach ($examples as $type => $example) {
            AIService::generateQuestions(
                1,
                'Developer',
                'medium',
                'Job Interview',
                'openai',
                'I improved a deployment process.',
                null,
                [$type]
            );

            $prompt = $prompts[array_key_last($prompts)];
            $this->assertStringContainsString("Only generate questions from these requested question types: {$type}.", $prompt);
            $this->assertStringContainsString(json_encode(['questions' => [$example]]), $prompt);
            $this->assertStringContainsString('preserving each requested question type', $prompt);
            $this->assertStringNotContainsString('Create behavioral and experience-based questions', $prompt);
        }
    }

    public function test_live_follow_up_prompt_keeps_the_required_type_for_the_final_question(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $prompt = '';
        Http::fake([
            'api.openai.com/*' => function ($request) use (&$prompt) {
                $prompt = (string) data_get($request->data(), 'messages.1.content', '');

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => 'Thank you. What motivates you to pursue the Developer role?'],
                    ]],
                ]);
            },
        ]);

        $session = (object) [
            'target_position' => 'Developer',
            'difficulty' => 'medium',
            'question_types' => json_encode(['Personal']),
            'interview_focus' => 'Job Interview',
            'accommodation_profile' => [],
        ];

        AIService::generateChatReply(
            $session,
            [],
            'I improved a deployment process.',
            'openai',
            true,
            null,
            [],
            null,
            'Personal'
        );

        $this->assertStringContainsString('The NEXT question must be Personal.', $prompt);
        $this->assertStringContainsString('The required Personal type takes priority', $prompt);
        $this->assertStringContainsString('FINAL question', $prompt);
    }

    public function test_generated_seaman_questions_exclude_an_unrelated_software_question(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => json_encode([
                        'questions' => [
                            'How would you debug a cloud software deployment for the Seaman role?',
                            'How would you check lifeboat safety equipment before a vessel departure?',
                        ],
                    ])],
                ]],
            ]),
        ]);

        $questions = AIService::generateQuestions(
            2,
            'Seaman',
            'medium',
            'Job Interview',
            'openai',
            null,
            'Inspect deck safety equipment before departure.',
            ['Technical']
        );

        $this->assertSame(['How would you check lifeboat safety equipment before a vessel departure?'], $questions);
    }

    public function test_question_generation_fills_ten_slots_from_two_short_provider_responses(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $prompts = [];
        Http::fake([
            'api.openai.com/*' => function ($request) use (&$prompts) {
                $prompts[] = (string) data_get($request->data(), 'messages.1.content', '');
                $start = count($prompts) === 1 ? 1 : 6;

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode([
                            'questions' => array_map(
                                fn (int $number): string => "How would you handle Developer project task {$number}?",
                                range($start, $start + 4)
                            ),
                        ])],
                    ]],
                ]);
            },
        ]);

        $result = AIService::generateQuestionsWithProvider(
            10,
            'Developer',
            'medium',
            'Job Interview',
            'openai',
            requestOptions: ['max_providers' => 1, 'generation_attempts' => 2]
        );

        $this->assertTrue($result['complete']);
        $this->assertSame(10, $result['requested_count']);
        $this->assertSame(10, $result['generated_count']);
        $this->assertCount(10, $result['questions']);
        $this->assertCount(10, array_unique($result['questions']));
        $this->assertCount(2, $prompts);
        $this->assertStringContainsString('Generate 10 mock interview questions', $prompts[0]);
        $this->assertStringContainsString('Generate 5 mock interview questions', $prompts[1]);
        $this->assertStringContainsString('How would you handle Developer project task 1?', $prompts[1]);
    }

    public function test_question_generation_ignores_existing_and_duplicate_questions_while_filling_batch(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $calls = 0;
        Http::fake([
            'api.openai.com/*' => function () use (&$calls) {
                $calls++;
                $questions = $calls === 1
                    ? [
                        'How would you plan a Developer release?',
                        'How would you debug a Developer API outage?',
                        'How would you write a Developer draft?',
                    ]
                    : [
                        'How would you debug a Developer API outage!',
                        'How would you review a Developer code change?',
                        'How would you test a Developer deployment?',
                    ];

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode(['questions' => $questions])],
                    ]],
                ]);
            },
        ]);

        $result = AIService::generateQuestionsWithProvider(
            3,
            'Developer',
            'medium',
            'Job Interview',
            'openai',
            requestOptions: [
                'max_providers' => 1,
                'generation_attempts' => 2,
                'exclude_question_texts' => ['How would you plan a Developer release?'],
                'question_validator' => fn (string $question): bool => ! str_contains($question, 'draft'),
            ]
        );

        $this->assertTrue($result['complete']);
        $this->assertSame(3, $result['generated_count']);
        $this->assertSame([
            'How would you debug a Developer API outage?',
            'How would you review a Developer code change?',
            'How would you test a Developer deployment?',
        ], $result['questions']);
        $this->assertSame(2, $calls);
    }

    public function test_question_generation_preserves_valid_partial_output_after_a_later_request_fails(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $calls = 0;
        Http::fake([
            'api.openai.com/*' => function () use (&$calls) {
                $calls++;
                if ($calls === 1) {
                    return Http::response([
                        'choices' => [[
                            'finish_reason' => 'stop',
                            'message' => ['content' => json_encode([
                                'questions' => ['How would you investigate a Developer deployment issue?'],
                            ])],
                        ]],
                    ]);
                }

                return Http::response([], 503);
            },
        ]);

        $result = AIService::generateQuestionsWithProvider(
            3,
            'Developer',
            'medium',
            'Job Interview',
            'openai',
            requestOptions: ['max_providers' => 1, 'generation_attempts' => 2, 'attempts' => 1]
        );

        $this->assertFalse($result['complete']);
        $this->assertSame(3, $result['requested_count']);
        $this->assertSame(1, $result['generated_count']);
        $this->assertSame('openai', $result['provider']);
        $this->assertSame(['How would you investigate a Developer deployment issue?'], $result['questions']);
        $this->assertSame(2, $calls);
    }

    public function test_strict_requested_provider_does_not_credit_a_different_provider(): void
    {
        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);
        Http::fake(['*' => Http::response(['questions' => ['How would you debug a Developer issue?']])]);

        $result = AIService::generateQuestionsWithProvider(
            1,
            'Developer',
            'medium',
            'Job Interview',
            'local',
            requestOptions: ['only_requested_provider' => true]
        );

        $this->assertFalse($result['complete']);
        $this->assertSame([], $result['questions']);
        $this->assertSame([], $result['attempted_providers']);
        Http::assertNothingSent();
    }

    public function test_expired_question_generation_deadline_skips_provider_requests(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);
        Http::fake(['*' => Http::response(['questions' => ['How would you debug a Developer issue?']])]);

        $result = AIService::generateQuestionsWithProvider(
            1,
            'Developer',
            'medium',
            'Job Interview',
            'openai',
            requestOptions: [
                'only_requested_provider' => true,
                'request_deadline_at' => microtime(true) - 1,
            ]
        );

        $this->assertFalse($result['complete']);
        $this->assertSame([], $result['attempted_providers']);
        Http::assertNothingSent();
    }
}
