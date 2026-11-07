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
}
