<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AiProvider;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
use App\Services\AIService;
use App\Services\QuestionDatasetProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InterviewChatReplyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_retried_text_and_hybrid_answers_reuse_the_same_follow_up(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $capturedPrompts = [];
        Http::fake([
            'api.openai.com/*' => function ($request) use (&$capturedPrompts) {
                $capturedPrompts[] = (string) data_get($request->data(), 'messages.1.content', '');

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => [
                            'content' => 'Thanks for sharing. How did you measure the reduction in the approval queue handoff time for the Developer role?',
                        ],
                    ]],
                ], 200);
            },
        ]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        foreach (['text', 'hybrid'] as $mode) {
            [$session, $question] = $this->interview($user, $category, $mode);
            $payload = [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'response_mode' => $mode,
                'answer_text' => 'I redesigned the approval queue, resolved duplicate invoices, and reduced handoff time.',
                'conversation_context' => json_encode([
                    ['role' => 'user', 'content' => 'I built a space rocket.'],
                ]),
            ];

            $first = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
                ->postJson(route('interview.chatReply'), $payload)
                ->assertOk()
                ->assertJsonPath('success', true);
            $second = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id])
                ->postJson(route('interview.chatReply'), $payload)
                ->assertOk()
                ->assertJsonPath('success', true);

            $this->assertSame($first->json('next_question_id'), $second->json('next_question_id'));
            $this->assertSame($first->json('next_question_text'), $second->json('next_question_text'));
            $this->assertSame('ai_generated', $first->json('source_type'));
            $this->assertStringNotContainsStringIgnoringCase('you mentioned', $first->json('next_question_text'));
            $this->assertStringContainsString('approval queue', strtolower($first->json('next_question_text')));
            $this->assertStringNotContainsString('space rocket', strtolower($first->json('next_question_text')));
            $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
            $this->assertSame($mode, InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail()->response_mode);
        }

        $this->assertNotEmpty($capturedPrompts);
        foreach ($capturedPrompts as $prompt) {
            $this->assertStringNotContainsString('Source dataset:', $prompt);
            $this->assertStringNotContainsString('Dataset examples', $prompt);
        }
    }

    public function test_disabled_ai_follow_up_does_not_use_a_local_question_fallback(): void
    {
        Setting::setVal('int_follow_up', false, 'interview', 'boolean');
        Storage::fake('local');
        config([
            'services.ai_transcription.provider_priority' => '',
            'services.local_speech.enabled' => false,
        ]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        [$voiceSession, $voiceQuestion] = $this->interview($user, $category, 'voice');
        $voiceResponse = $this->actingAs($user)
            ->withSession(['active_interview_id' => $voiceSession->id])
            ->post(route('interview.chatReply'), [
                'session_id' => $voiceSession->id,
                'question_id' => $voiceQuestion->id,
                'response_mode' => 'voice',
                'answer_text' => 'I secretly built a space rocket.',
                'speech_transcript' => 'I secretly built a space rocket.',
                'voice_audio' => UploadedFile::fake()
                    ->createWithContent('answer.webm', str_repeat('sample-audio-', 200))
                    ->mimeType('audio/webm'),
            ])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ai_interview_follow_up_disabled');

        $this->assertSame(0, InterviewAnswer::where('interview_session_id', $voiceSession->id)->count());

        [$skippedSession, $skippedQuestion] = $this->interview($user, $category, 'text');
        $skippedResponse = $this->actingAs($user)
            ->withSession(['active_interview_id' => $skippedSession->id])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $skippedSession->id,
                'question_id' => $skippedQuestion->id,
                'response_mode' => 'text',
                'is_skipped' => true,
            ])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ai_interview_follow_up_disabled');

        $this->assertSame(0, InterviewAnswer::where('interview_session_id', $skippedSession->id)->count());
    }

    public function test_provider_question_outside_dataset_examples_is_saved(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $generated = 'What interests you about improving reliability as a Developer?';
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => $generated]]],
        ])]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $datasetQuestions = collect(QuestionDatasetProvider::forCategory($category)['questions'] ?? [])
            ->pluck('question_text')->all();
        $this->assertNotContains($generated, $datasetQuestions);
        [$session, $question] = $this->interview($user, $category, 'text');

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I build small web services and want to make them more reliable.',
            ])
            ->assertOk()
            ->assertJsonPath('next_question_text', $generated)
            ->assertJsonPath('source_type', 'ai_generated');

        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'question_text' => $generated,
            'ai_provider' => 'openai',
        ]);
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.openai.com'));
    }

    public function test_grounded_provider_questions_with_natural_wording_and_job_terms_are_not_backups(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);

        $cases = [
            [
                'answer' => 'I coordinated the handoff with the finance team while building a new release workflow.',
                'question' => 'How did you coordinate the handoff with the finance team as a Developer?',
            ],
            [
                'answer' => 'I designed a support workflow after customers gave feedback about slow handoffs.',
                'question' => 'What customer feedback shaped the support workflow you designed as a Developer?',
            ],
            [
                'answer' => 'I designed the release workflow and stored a JSON API key in a secure vault.',
                'question' => 'How did you protect the JSON API key in the release workflow you designed as a Developer?',
            ],
            [
                'answer' => 'I tested the deployment checks and verified the release workflow before customers used it.',
                'question' => 'How did you test the deployment checks in the Developer role?',
                'type' => 'Technical',
            ],
            [
                'answer' => 'Nagdisenyo ako ng deployment checklist at sinuri ang release bago ito inilunsad.',
                'question' => 'Paano mo sinuri ang deployment checklist para sa Developer role?',
                'type' => 'Technical',
            ],
        ];
        $replies = array_column($cases, 'question');
        Http::fake(['api.openai.com/*' => function () use (&$replies) {
            return Http::response([
                'choices' => [['finish_reason' => 'stop', 'message' => ['content' => array_shift($replies)]]],
            ]);
        }]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        foreach ($cases as $case) {
            [$session, $question] = $this->interview($user, $category, 'text');
            if (isset($case['type'])) {
                $session->forceFill(['question_types' => json_encode([$case['type']])])->save();
            }
            $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
                ->postJson(route('interview.chatReply'), [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'answer_text' => $case['answer'],
                ])
                ->assertOk()
                ->assertJsonPath('next_question_text', $case['question'])
                ->assertJsonPath('source_type', 'ai_generated')
                ->assertJsonPath('ai_provider', 'openai');

            $this->assertSame(0, Question::where('interview_session_id', $session->id)
                ->where('source_type', 'local_question_fallback')->count());
        }

        Http::assertSentCount(count($cases));
    }

    public function test_provider_generates_a_follow_up_without_dataset_context(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $generated = 'What interests you about improving reliability as a Developer?';
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => $generated]]],
        ])]);
        $session = new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']);
        $answer = 'I build small web services and want to make them more reliable.';
        $history = [['question' => 'Tell me about a work improvement you made.', 'answer' => $answer]];
        $usedProvider = null;

        $reply = AIService::generateChatReply(
            $session, $history, $answer, 'openai', false, null, [], null, null, null, $usedProvider
        );

        $this->assertSame($generated, $reply);
        $this->assertSame('openai', $usedProvider);
        Http::assertSentCount(1);
    }

    public function test_start_without_a_working_provider_does_not_create_local_questions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $this->actingAs($user)
            ->from(route('interview.setup'))
            ->post(route('interview.start'), [
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => 'Developer',
                'num_questions' => 3,
                'response_mode' => 'text',
                'interviewer_avatar' => 'friendly_recruiter',
                'ai_provider' => 'local',
            ])
            ->assertRedirect(route('interview.setup'))
            ->assertSessionHasErrors('ai_provider');

        $this->assertDatabaseCount('interview_sessions', 0);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_rejected_provider_question_is_retried_with_provider_instead_of_local_fallback(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config([
            'services.interview_follow_up.max_retries' => 3,
            'services.interview_follow_up.max_providers' => 1,
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $generated = 'What interests you about improving reliability as a Developer?';
        $replies = ['What is your favorite color?', $generated];
        $prompts = [];
        Http::fake(['api.openai.com/*' => function ($request) use (&$replies, &$prompts) {
            $prompts[] = (string) data_get($request->data(), 'messages.1.content', '');
            return Http::response([
                'choices' => [['finish_reason' => 'stop', 'message' => ['content' => array_shift($replies)]]],
            ]);
        }]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I build small web services and want to make them more reliable.',
            ])
            ->assertOk()
            ->assertJsonPath('next_question_text', $generated);

        Http::assertSentCount(2);
        $this->assertCount(2, $prompts);
        $this->assertStringContainsString('include one distinctive noun or action word from the latest candidate answer exactly as written in the QUESTION itself', $prompts[1]);
        $this->assertStringContainsString('do not invent candidate history or repeat an earlier question', $prompts[1]);
        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
    }

    public function test_repeated_provider_question_is_retried_instead_of_replaced_with_local_text(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config([
            'services.interview_follow_up.max_retries' => 3,
            'services.interview_follow_up.max_providers' => 1,
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $repeated = 'What interests you about improving reliability as a Developer?';
        $generated = 'Which strength would help you improve reliability in the Developer role?';
        $replies = [$repeated, $generated];
        Http::fake(['api.openai.com/*' => function () use (&$replies) {
            return Http::response([
                'choices' => [['finish_reason' => 'stop', 'message' => ['content' => array_shift($replies)]]],
            ]);
        }]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $question->forceFill(['question_text' => $repeated])->save();

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I build small web services and want to make them more reliable.',
            ])
            ->assertOk()
            ->assertJsonPath('next_question_text', $generated);

        Http::assertSentCount(2);
        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'question_text' => $generated,
            'ai_provider' => 'openai',
        ]);
    }

    public function test_second_ai_provider_can_generate_question_and_is_recorded_as_source(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config([
            'services.interview_follow_up.max_retries' => 3,
            'services.interview_follow_up.max_providers' => 2,
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);
        $generated = 'What interests you about improving reliability as a Developer?';
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'unavailable'], 503),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => $generated]]]]],
            ]),
        ]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I build small web services and want to make them more reliable.',
            ])
            ->assertOk()
            ->assertJsonPath('next_question_text', $generated)
            ->assertJsonPath('ai_provider', 'gemini');

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.openai.com'));
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'generativelanguage.googleapis.com'));
        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'question_text' => $generated,
            'ai_provider' => 'gemini',
        ]);
    }

    public function test_seaman_session_uses_a_grounded_backup_when_provider_output_is_unreliable(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config([
            'services.interview_follow_up.max_retries' => 1,
            'services.interview_follow_up.max_providers' => 1,
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => 'You mentioned inspecting the lifeboat. How did you debug the cloud software deployment for the Seaman role?'],
                ]],
            ]),
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $session->forceFill(['target_position' => 'Seaman', 'num_questions' => 3])->save();
        $question->forceFill(['question_text' => 'Describe a time you inspected shipboard safety equipment.'])->save();

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'response_mode' => 'text',
                'answer_text' => 'I inspected the lifeboat before departure, found expired flares, and reported the safety issue to the captain.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source_type', 'local_follow_up_fallback')
            ->assertJsonPath('ai_provider', null);

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.openai.com'));
        $this->assertStringContainsString('Seaman', (string) $response->json('next_question_text'));
        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
        $this->assertDatabaseHas('questions', [
            'id' => $response->json('next_question_id'),
            'source_type' => 'local_follow_up_fallback',
            'ai_provider' => null,
        ]);
        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_provider_failure_saves_the_answer_and_creates_a_local_follow_up(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429),
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'response_mode' => 'text',
                'answer_text' => 'I improved the support workflow and checked the result with the team.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source_type', 'local_follow_up_fallback')
            ->assertJsonPath('ai_provider', null);

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.openai.com'));
        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
        $this->assertDatabaseHas('questions', [
            'id' => $response->json('next_question_id'),
            'source_type' => 'local_follow_up_fallback',
            'ai_provider' => null,
        ]);
        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_local_follow_up_covers_selected_question_types_and_skipped_answers(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        foreach ([
            ['type' => 'Behavioral', 'answer_text' => 'I redesigned the approval queue and reduced the handoff delay with the team.'],
            ['type' => 'Situational', 'answer_text' => 'I redesigned the approval queue and reduced the handoff delay with the team.'],
            ['type' => 'Technical', 'answer_text' => 'I redesigned the approval queue and reduced the handoff delay with the team.'],
            ['type' => 'Personal', 'answer_text' => 'I redesigned the approval queue and reduced the handoff delay with the team.'],
            ['type' => 'Personal', 'is_skipped' => true],
        ] as $case) {
            [$session, $question] = $this->interview($user, $category, 'text');
            $session->forceFill(['question_types' => json_encode([$case['type']]), 'num_questions' => 3])->save();

            $response = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
                ->postJson(route('interview.chatReply'), array_merge([
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                ], array_diff_key($case, ['type' => true])))
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('source_type', 'local_follow_up_fallback')
                ->assertJsonPath('ai_provider', null);

            $this->assertSame(1, substr_count((string) $response->json('next_question_text'), '?'), $case['type']);
            $this->assertDatabaseHas('questions', [
                'id' => $response->json('next_question_id'),
                'type' => $case['type'],
                'source_type' => 'local_follow_up_fallback',
                'ai_provider' => null,
            ]);
            $this->assertSame(1, InterviewAnswer::where('interview_session_id', $session->id)->count());
        }
    }

    public function test_local_follow_ups_remain_distinct_across_a_full_session_when_provider_stays_unavailable(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $session->forceFill(['num_questions' => 6, 'question_types' => json_encode(['Behavioral'])])->save();
        $texts = [];

        for ($turn = 0; $turn < 5; $turn++) {
            $response = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
                ->postJson(route('interview.chatReply'), [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'answer_text' => 'I improved the support workflow and checked the result with the team.',
                ]);
            $this->assertSame(200, $response->status(), "Fallback failed at turn {$turn}: ".(string) $response->json('error'));
            $response->assertJsonPath('source_type', 'local_follow_up_fallback');

            $text = (string) $response->json('next_question_text');
            $this->assertNotContains($text, $texts);
            $texts[] = $text;
            $question = Question::findOrFail($response->json('next_question_id'));
        }

        $this->assertSame(6, Question::where('interview_session_id', $session->id)->count());
        $this->assertSame(5, InterviewAnswer::where('interview_session_id', $session->id)->count());
    }

    public function test_local_follow_ups_do_not_exhaust_after_twenty_nine_skipped_answers(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $session->forceFill(['num_questions' => 30, 'question_types' => json_encode(['Behavioral'])])->save();
        $texts = [];

        for ($turn = 0; $turn < 29; $turn++) {
            $response = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
                ->postJson(route('interview.chatReply'), [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'is_skipped' => true,
                ]);
            $this->assertSame(200, $response->status(), "Fallback failed at turn {$turn}: ".(string) $response->json('error'));
            $response->assertJsonPath('source_type', 'local_follow_up_fallback');

            $text = (string) $response->json('next_question_text');
            $this->assertNotContains($text, $texts, "Duplicate fallback at turn {$turn}");
            $texts[] = $text;
            $question = Question::findOrFail($response->json('next_question_id'));
        }

        $this->assertSame(30, Question::where('interview_session_id', $session->id)->count());
        $this->assertSame(29, InterviewAnswer::where('interview_session_id', $session->id)->count());
    }

    public function test_local_follow_up_returns_to_nurse_role_after_off_topic_software_answer(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $session->forceFill(['target_position' => 'Nurse', 'num_questions' => 3])->save();

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I debugged the cloud software deployment and resolved API errors for a website.',
            ])->assertOk()
            ->assertJsonPath('source_type', 'local_follow_up_fallback');

        $text = (string) $response->json('next_question_text');
        $this->assertStringContainsString('Nurse', $text);
        $this->assertDoesNotMatchRegularExpression('/\b(?:website|software|cloud|deployment|API)\b/i', $text);
    }

    public function test_local_follow_up_uses_the_users_selected_philippine_language_and_question_type(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $category = $this->category();
        foreach ([
            [
                'language' => 'fil',
                'type' => 'Technical',
                'answer' => 'Sinuri ko ang deployment checklist at inayos ang mga error bago ilunsad ang release.',
                'question_pattern' => '/\b(?:paano|pamamaraan|paraan|hakbang|proseso|sinuri|suriin)\b/iu',
            ],
            [
                'language' => 'tl',
                'type' => 'Personal',
                'answer' => 'Interesado ako sa pagbuo ng mga serbisyo at pagtulong sa aking koponan.',
                'question_pattern' => '/\b(?:lakas|interes|motibasyon|katangian|layunin)\b/iu',
            ],
            [
                'language' => 'ceb',
                'type' => 'Situational',
                'answer' => 'Gisusi nako ang deployment checklist ug giayo ang mga problema sa release.',
                'question_pattern' => '/\b(?:kon|pananglit|unsa|unsaon|buhaton)\b/iu',
            ],
        ] as $case) {
            $user = User::factory()->create([
                'is_admin' => false,
                'status' => 'active',
                'preferred_language' => $case['language'],
            ]);
            [$session, $question] = $this->interview($user, $category, 'text');
            $session->forceFill(['question_types' => json_encode([$case['type']]), 'num_questions' => 3])->save();

            $response = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
                ->postJson(route('interview.chatReply'), [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'answer_text' => $case['answer'],
                ])->assertOk()
                ->assertJsonPath('source_type', 'local_follow_up_fallback');

            $text = (string) $response->json('next_question_text');
            $this->assertSame(1, preg_match($case['question_pattern'], $text), $case['language'].': '.$text);
            $this->assertDatabaseHas('questions', [
                'id' => $response->json('next_question_id'),
                'type' => $case['type'],
                'source_type' => 'local_follow_up_fallback',
            ]);
        }
    }

    public function test_saved_answer_and_backup_question_are_reused_after_provider_recovery(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $providerAvailable = false;
        $generated = 'What interests you about improving reliability as a Developer?';
        Http::fake(['api.openai.com/*' => function () use (&$providerAvailable, $generated) {
            return $providerAvailable
                ? Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => $generated]]]])
                : Http::response(['error' => ['message' => 'rate limited']], 429);
        }]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $answerText = 'I build small web services and want to make them more reliable.';

        $first = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => $answerText,
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source_type', 'local_follow_up_fallback')
            ->assertJsonPath('ai_provider', null);

        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());

        $saved = InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail();
        $savedId = $saved->id;
        $saved->forceFill(['voice_recording_path' => 'interviews/keep-this-recording.webm'])->save();

        $providerAvailable = true;

        $recovered = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'resume_saved_answer' => true,
                'answer_text' => 'This new text must not replace the saved answer.',
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source_type', 'local_follow_up_fallback')
            ->assertJsonPath('ai_provider', null)
            ->assertJsonPath('next_question_id', $first->json('next_question_id'))
            ->assertJsonPath('next_question_text', $first->json('next_question_text'));

        $providerAvailable = false;
        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'resume_saved_answer' => true,
            ])->assertOk()
            ->assertJsonPath('next_question_id', $recovered->json('next_question_id'))
            ->assertJsonPath('source_type', 'local_follow_up_fallback');

        $saved->refresh();
        $this->assertSame($savedId, $saved->id);
        $this->assertSame($answerText, $saved->answer_text);
        $this->assertSame('interviews/keep-this-recording.webm', $saved->voice_recording_path);
        $this->assertSame(1, InterviewAnswer::where('interview_session_id', $session->id)->count());
        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Question::where('interview_session_id', $session->id)->where('source_type', 'local_follow_up_fallback')->count());
    }

    public function test_resume_requires_a_saved_answer(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'resume_saved_answer' => true,
            ])->assertStatus(409);

        $this->assertSame(0, InterviewAnswer::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Question::where('interview_session_id', $session->id)->count());
    }

    public function test_resume_replaces_an_unanswered_legacy_backup_but_keeps_an_answered_one(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $generated = 'How did you measure the reduced approval queue handoff delay in the Developer role?';
        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => $generated]]],
        ])]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $answerText = 'I stabilized the approval queue and reduced the handoff delay.';

        foreach ([false, true] as $legacyQuestionAnswered) {
            [$session, $question] = $this->interview($user, $category, 'text');
            InterviewAnswer::create([
                'interview_session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => $answerText,
                'response_mode' => 'text',
                'score' => 0,
            ]);
            $legacy = Question::create([
                'category_id' => $category->id,
                'interview_session_id' => $session->id,
                'question_text' => 'What did you do next in the Developer role?',
                'difficulty' => 'medium',
                'type' => 'Behavioral',
                'status' => 'active',
                'source_name' => 'Built-in backup interview question',
                'source_type' => 'local_question_fallback',
            ]);
            if ($legacyQuestionAnswered) {
                InterviewAnswer::create([
                    'interview_session_id' => $session->id,
                    'question_id' => $legacy->id,
                    'answer_text' => 'I documented the next step.',
                    'response_mode' => 'text',
                    'score' => 0,
                ]);
            }
            $session->forceFill(['current_question_index' => 1])->save();

            $response = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
                ->postJson(route('interview.chatReply'), [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'resume_saved_answer' => true,
                ])->assertOk();

            if ($legacyQuestionAnswered) {
                $response->assertJsonPath('next_question_id', $legacy->id)
                    ->assertJsonPath('source_type', 'local_question_fallback');
                $this->assertDatabaseHas('questions', ['id' => $legacy->id]);
            } else {
                $response->assertJsonPath('next_question_text', $generated)
                    ->assertJsonPath('source_type', 'ai_generated')
                    ->assertJsonPath('ai_provider', 'openai');
                $this->assertDatabaseMissing('questions', ['id' => $legacy->id]);
                $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
            }
        }

        Http::assertSentCount(1);
    }

    public function test_session_views_hide_an_unanswered_legacy_backup_and_offer_saved_answer_retry(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        [$session, $question] = $this->interview($user, $category, 'text');
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I stabilized the approval queue and reduced the handoff delay.',
            'response_mode' => 'text',
            'score' => 0,
        ]);
        $legacy = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'What did you do next in the Developer role?',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
            'source_type' => 'local_question_fallback',
        ]);
        $session->forceFill([
            'current_question_index' => 1,
            'session_state' => json_encode([
                'has_started' => true,
                'currentQIdx' => 1,
                'questions' => [
                    ['id' => $question->id, 'question_text' => $question->question_text],
                    ['id' => $legacy->id, 'question_text' => $legacy->question_text, 'source_type' => 'local_question_fallback'],
                ],
                'answersData' => [
                    ['text' => 'I stabilized the approval queue and reduced the handoff delay.'],
                    ['text' => 'Stale answer slot for the unasked backup.'],
                ],
                'chatHistory' => [
                    ['role' => 'interviewer', 'text' => $question->question_text],
                    ['role' => 'user', 'text' => 'I stabilized the approval queue and reduced the handoff delay.'],
                    ['role' => 'interviewer', 'text' => $legacy->question_text],
                ],
            ]),
        ])->save();

        foreach ([
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Mobile Safari/537.36',
        ] as $userAgent) {
            $response = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id])
                ->withHeader('User-Agent', $userAgent)
                ->get(route('interview.session'))
                ->assertOk();
            $html = $response->getContent();
            $this->assertSame(1, preg_match('/const initialQuestions = (\[.*?\]);\s*let questions =/s', $html, $initialMatch));
            $initialQuestions = json_decode($initialMatch[1], true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([$question->id], array_column($initialQuestions, 'id'));
            $this->assertSame(1, preg_match('/const savedSessionState = (\{.*?\});\s*const initialQuestions =/s', $html, $savedMatch));
            $savedState = json_decode($savedMatch[1], true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([$question->id], array_column($savedState['questions'], 'id'));
            $this->assertCount(1, $savedState['answersData']);
            $this->assertSame(0, $savedState['currentQIdx']);
            $this->assertSame([
                $question->question_text,
                'I stabilized the approval queue and reduced the handoff delay.',
            ], array_column($savedState['chatHistory'], 'text'));
            $this->assertSame(1, preg_match('/let pendingSavedAnswerRetry = (\d+|null) \?/', $html, $retryMatch));
            $this->assertSame((string) $question->id, $retryMatch[1]);
        }
    }

    public function test_session_views_keep_an_unanswered_live_backup_after_refresh(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $backup = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I improved the support workflow and checked the result with the team.',
            ])->assertOk()->assertJsonPath('source_type', 'local_follow_up_fallback');

        $backupId = (int) $backup->json('next_question_id');
        $session->forceFill(['session_state' => json_encode([
            'has_started' => true,
            'currentQIdx' => 1,
            'questions' => [
                ['id' => $question->id, 'question_text' => $question->question_text],
                ['id' => $backupId, 'question_text' => $backup->json('next_question_text'), 'source_type' => 'local_follow_up_fallback'],
            ],
            'answersData' => [
                ['text' => 'I improved the support workflow and checked the result with the team.'],
                ['text' => ''],
            ],
        ])])->save();

        foreach ([
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Mobile Safari/537.36',
        ] as $userAgent) {
            $html = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id])
                ->withHeader('User-Agent', $userAgent)
                ->get(route('interview.session'))
                ->assertOk()
                ->getContent();

            $this->assertSame(1, preg_match('/const initialQuestions = (\[.*?\]);\s*let questions =/s', $html, $initialMatch));
            $initialQuestions = json_decode($initialMatch[1], true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([$question->id, $backupId], array_column($initialQuestions, 'id'));
            $this->assertSame('local_follow_up_fallback', $initialQuestions[1]['source_type']);
            $this->assertSame(1, preg_match('/const savedSessionState = (\{.*?\});\s*const initialQuestions =/s', $html, $savedMatch));
            $savedState = json_decode($savedMatch[1], true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([$question->id, $backupId], array_column($savedState['questions'], 'id'));
            $this->assertSame(1, $savedState['currentQIdx']);
        }
    }

    public function test_session_views_offer_a_new_ai_interview_for_an_orphan_legacy_backup(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $question->forceFill(['source_type' => 'local_question_fallback'])->save();

        foreach ([
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Mobile Safari/537.36',
        ] as $userAgent) {
            $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id])
                ->withHeader('User-Agent', $userAgent)
                ->get(route('interview.session'))
                ->assertOk()
                ->assertSee('Start a new AI interview')
                ->assertSee('Start new interview')
                ->assertSee('href="'.route('interview.setup').'"', false)
                ->assertDontSee($question->question_text)
                ->assertDontSee('id="backupQuestionBadge"', false)
                ->assertDontSee('No questions found');
        }
    }

    public function test_repeated_provider_failures_preserve_one_answer_and_one_backup_question(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        config(['services.interview_follow_up.max_retries' => 1]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $session->forceFill(['num_questions' => 3])->save();

        $first = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id, 'active_interview_provider' => 'openai'])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I improved the support workflow and checked the result with the team.',
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source_type', 'local_follow_up_fallback')
            ->assertJsonPath('ai_provider', null);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'resume_saved_answer' => true,
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('next_question_id', $first->json('next_question_id'))
            ->assertJsonPath('source_type', 'local_follow_up_fallback');

        $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, InterviewAnswer::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Question::where('interview_session_id', $session->id)->where('source_type', 'local_follow_up_fallback')->count());
    }

    public function test_disabled_ai_follow_up_does_not_use_local_conversation_questions(): void
    {
        Setting::setVal('int_follow_up', false, 'interview', 'boolean');
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        [$session, $question] = $this->interview($user, $this->category(), 'text');
        $session->forceFill(['num_questions' => 4])->save();

        $first = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I redesigned the approval queue and reduced the waiting time by 30 percent.',
            ])->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ai_interview_follow_up_disabled');

        $this->assertSame(1, Question::where('interview_session_id', $session->id)->count());
    }

    private function category(): Category
    {
        return Category::create([
            'title' => 'Job Interview',
            'description' => 'Job interview practice',
            'status' => 'active',
            'type' => 'core',
        ]);
    }

    private function interview(User $user, Category $category, string $mode): array
    {
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 2,
            'coach_focus_mode' => 'balanced',
            'response_mode' => $mode,
            'status' => 'in_progress',
        ]);
        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a work improvement you made.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        return [$session, $question];
    }
}
