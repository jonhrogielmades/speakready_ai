<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InterviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnvValues = [];

    protected function tearDown(): void
    {
        Cache::flush();

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

    public function test_setup_db_route_is_not_publicly_available(): void
    {
        $this->get('/setup-db')->assertNotFound();
    }

    public function test_interview_session_requires_an_active_session_key(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('interview.session'))
            ->assertRedirect(route('interview.setup'))
            ->assertSessionHas('message', 'Start an interview session first.');
    }

    public function test_interview_session_forgets_stale_or_completed_active_session(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $session->update(['status' => 'completed']);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'active_interview_provider' => 'openai',
                'active_interview_context' => 'interview',
            ])
            ->get(route('interview.session'))
            ->assertRedirect(route('interview.setup'))
            ->assertSessionHas('message', 'Your interview session is no longer active.')
            ->assertSessionMissing('active_interview_id')
            ->assertSessionMissing('active_interview_provider')
            ->assertSessionMissing('active_interview_context');
    }

    public function test_interview_session_renders_for_current_in_progress_session(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->get(route('interview.session'))
            ->assertOk()
            ->assertSee('Describe a difficult project.');
    }

    public function test_user_cannot_answer_an_active_session_owned_by_another_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($otherUser, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'question_id' => $question->id,
                'answer_text' => 'This should not be accepted.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_user_cannot_answer_a_question_from_another_session(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $otherSession = $this->sessionFor($otherUser, $category);
        $otherQuestion = $this->sessionQuestion($otherSession, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'question_id' => $otherQuestion->id,
                'answer_text' => 'This question belongs elsewhere.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $otherQuestion->id,
        ]);
    }

    public function test_user_can_answer_active_reusable_category_question(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Tell me about yourself.',
            'difficulty' => $session->difficulty,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'question_id' => $question->id,
                'answer_text' => 'I build reliable software and communicate clearly.',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_user_cannot_finish_a_session_that_is_not_their_active_session(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $otherSession = $this->sessionFor($otherUser, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.finish'), ['session_id' => $otherSession->id])
            ->assertForbidden();
    }

    public function test_user_can_request_speech_for_their_active_question(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai',
            'services.openai.tts_enabled' => true,
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::response('fake-audio', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('fake-audio');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'
            && $request['model'] === 'gpt-4o-mini-tts'
            && $request['input'] === $question->question_text);
    }

    public function test_user_can_request_speech_for_active_session_closing_text(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai',
            'services.openai.tts_enabled' => true,
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::response('closing-audio', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $closingText = 'Thank you for walking me through your answers today. Your responses are being analyzed for feedback.';

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), [
                'session_id' => $session->id,
                'speech_text' => $closingText,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('closing-audio');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'
            && $request['model'] === 'gpt-4o-mini-tts'
            && $request['input'] === $closingText);
    }

    public function test_user_can_request_speech_with_gemini_tts_provider(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'gemini',
            'services.gemini.tts_model' => 'gemini-3.1-flash-tts-preview',
            'services.gemini.tts_voice' => 'Kore',
            'services.gemini.tts_style' => 'Say clearly',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/interactions' => Http::response([
                'interaction' => [
                    'output_audio' => [
                        'data' => base64_encode(str_repeat("\0", 480)),
                    ],
                ],
            ], 200, [
                'Content-Type' => 'application/json',
            ]),
        ]);

        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ]);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/wav');

        $this->assertStringStartsWith('RIFF', $response->getContent());

        Http::assertSent(fn ($request) => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/interactions'
            && $request['model'] === 'gemini-3.1-flash-tts-preview'
            && data_get($request->data(), 'generation_config.speech_config.0.voice') === 'Kore'
            && str_contains((string) $request['input'], $question->question_text));
    }

    public function test_user_can_request_speech_with_elevenlabs_tts_provider(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'elevenlabs',
            'services.elevenlabs.api_key' => 'sk_test-eleven-key',
            'services.elevenlabs.api_endpoint' => 'https://api.elevenlabs.io/v1',
            'services.elevenlabs.tts_model' => 'eleven_multilingual_v2',
            'services.elevenlabs.tts_voice_id' => 'JBFqnCBsd6RMkjVDRZzb',
            'services.elevenlabs.tts_output_format' => 'mp3_44100_128',
            'services.elevenlabs.tts_language_code' => '',
            'services.elevenlabs.tts_stability' => 0.45,
            'services.elevenlabs.tts_similarity_boost' => 0.75,
            'services.elevenlabs.tts_style' => 0.15,
            'services.elevenlabs.tts_speaker_boost' => true,
        ]);

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/JBFqnCBsd6RMkjVDRZzb?output_format=mp3_44100_128' => Http::response('eleven-audio', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('eleven-audio');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.elevenlabs.io/v1/text-to-speech/JBFqnCBsd6RMkjVDRZzb?output_format=mp3_44100_128'
            && $request->hasHeader('xi-api-key', 'sk_test-eleven-key')
            && $request['text'] === $question->question_text
            && $request['model_id'] === 'eleven_multilingual_v2'
            && data_get($request->data(), 'voice_settings.stability') === 0.45
            && data_get($request->data(), 'voice_settings.use_speaker_boost') === true);
    }

    public function test_speech_endpoint_does_not_call_paid_tts_when_disabled(): void
    {
        config([
            'services.ai_tts.enabled' => false,
            'services.openai.tts_enabled' => false,
        ]);
        Http::fake();

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.speech'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ])
            ->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_user_cannot_request_speech_for_another_users_question(): void
    {
        Http::fake();

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $otherSession = $this->sessionFor($otherUser, $category);
        $otherQuestion = $this->sessionQuestion($otherSession, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.speech'), [
                'session_id' => $session->id,
                'question_id' => $otherQuestion->id,
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_chat_reply_accepts_posted_session_id_when_session_key_is_missing(): void
    {
        Setting::setVal('int_follow_up', false, 'interview', 'boolean');

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['num_questions' => 2]);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I stabilized a migration and communicated risks clearly.',
                'response_mode' => 'text',
            ])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertSessionHas('active_interview_id', $session->id);

        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I stabilized a migration and communicated risks clearly.',
        ]);
    }

    public function test_user_can_transcribe_audio_for_their_active_question(): void
    {
        config(['services.openai.transcription_model' => 'gpt-transcribe']);

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => 'I delivered a stable migration.',
            ], 200),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active', 'preferred_language' => 'fil']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'previous_transcript' => 'Nagtrabaho ko sa customer support ug maayo ang tabang.',
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertOk()
            ->assertJson(['transcript' => 'I delivered a stable migration.'])
            ->assertJson(['transcription_source' => 'openai']);

        Http::assertSent(function ($request): bool {
            $parts = collect($request->data());
            $fields = $parts
                ->filter(fn ($part) => is_array($part) && isset($part['name']))
                ->groupBy('name')
                ->map(fn ($parts) => $parts->pluck('contents')->all());
            $filePart = $parts->first(fn ($part) => is_array($part) && ($part['name'] ?? null) === 'file');

            return $request->url() === 'https://api.openai.com/v1/audio/transcriptions'
                && data_get($fields, 'model.0') === 'gpt-transcribe'
                && data_get($fields, 'response_format.0') === 'json'
                && in_array('tl', (array) data_get($fields, 'languages[]', []), true)
                && in_array('en', (array) data_get($fields, 'languages[]', []), true)
                && in_array('STAR method', (array) data_get($fields, 'keywords[]', []), true)
                && in_array('Cebuano', (array) data_get($fields, 'keywords[]', []), true)
                && in_array('Bisaya', (array) data_get($fields, 'keywords[]', []), true)
                && in_array('tabang', (array) data_get($fields, 'keywords[]', []), true)
                && str_contains((string) data_get($fields, 'prompt.0'), 'Philippine job interview practice answer')
                && str_contains((string) data_get($fields, 'prompt.0'), 'Cebuano')
                && str_contains((string) data_get($fields, 'prompt.0'), 'Recent earlier transcript')
                && ($filePart['filename'] ?? null) === 'speech.webm';
        });
    }

    public function test_transcription_response_auto_corrects_common_word_errors(): void
    {
        config(['services.openai.transcription_model' => 'gpt-transcribe']);

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => 'teh api improovement helped alot because im responsable for qa.',
            ], 200),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertOk()
            ->assertJson([
                'transcript' => "the API improvement helped a lot because I'm responsible for QA.",
                'transcription_source' => 'openai',
            ]);

        Http::assertSent(function ($request): bool {
            $parts = collect($request->data());
            $fields = $parts
                ->filter(fn ($part) => is_array($part) && isset($part['name']))
                ->groupBy('name')
                ->map(fn ($parts) => $parts->pluck('contents')->all());

            return $request->url() === 'https://api.openai.com/v1/audio/transcriptions'
                && str_contains((string) data_get($fields, 'prompt.0'), 'Correct obvious word spelling and casing errors');
        });
    }

    public function test_empty_transcription_chunk_returns_successful_empty_result(): void
    {
        config([
            'services.ai_transcription.provider_priority' => 'openai',
            'services.openai.transcription_model' => 'gpt-transcribe',
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => '',
            ], 200),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertOk()
            ->assertJson([
                'transcript' => '',
                'transcription_source' => 'openai',
                'transcription_status' => 'empty',
            ]);
    }

    public function test_failed_transcription_provider_returns_service_error(): void
    {
        config([
            'services.ai_transcription.provider_priority' => 'openai',
            'services.openai.transcription_model' => 'gpt-transcribe',
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertStatus(503)
            ->assertJson([
                'error_code' => 'speech_transcription_failed',
                'transcription_status' => 'failed',
            ]);
    }

    public function test_user_can_transcribe_audio_with_gemini_when_openai_transcription_is_unavailable(): void
    {
        $this->setEnvValue('OPENAI_API_KEY', '');
        $this->setEnvValue('GEMINI_API_KEY', 'test-gemini-key');

        config([
            'services.ai_transcription.provider_priority' => 'openai,gemini',
            'services.gemini.transcription_model' => 'gemini-3.6-flash',
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'transcript' => 'Nakatabang ko sa customer ug maayo ang result.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active', 'preferred_language' => 'ceb']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['target_position' => 'Customer Service Representative']);
        $question = $this->sessionQuestion($session, $category);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'previous_transcript' => 'Ako ang ni-handle sa reklamo.',
                'audio' => UploadedFile::fake()->createWithContent('speech.webm', str_repeat('0', 2048)),
            ]);

        $response
            ->assertOk()
            ->assertJson(['transcript' => 'Nakatabang ko sa customer ug maayo ang result.'])
            ->assertJson(['transcription_source' => 'gemini']);

        Http::assertSent(function ($request): bool {
            $data = $request->data();
            $prompt = (string) data_get($data, 'contents.0.parts.0.text');

            return str_contains($request->url(), 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent')
                && data_get($data, 'contents.0.parts.1.inline_data.mime_type') === 'audio/webm'
                && data_get($data, 'contents.0.parts.1.inline_data.data') !== ''
                && str_contains($prompt, 'Cebuano')
                && str_contains($prompt, 'Bisaya')
                && str_contains($prompt, 'Do not translate')
                && str_contains($prompt, 'Recent earlier transcript')
                && str_contains($prompt, 'Customer Service Representative');
        });
    }

    public function test_rate_limited_transcription_provider_falls_back_and_is_temporarily_skipped(): void
    {
        $this->setEnvValue('OPENAI_API_KEY', 'test-openai-key');
        $this->setEnvValue('GEMINI_API_KEY', 'test-gemini-key');

        config([
            'services.ai_transcription.provider_priority' => 'openai,gemini',
            'services.ai_transcription.rate_limit_cooldown_seconds' => 30,
            'services.openai.transcription_model' => 'gpt-transcribe',
            'services.gemini.transcription_model' => 'gemini-3.6-flash',
        ]);

        $requestCounts = ['openai' => 0, 'gemini' => 0];
        Http::fake(function ($request) use (&$requestCounts) {
            if (str_contains($request->url(), 'api.openai.com/v1/audio/transcriptions')) {
                $requestCounts['openai']++;

                return Http::response(['error' => ['message' => 'quota exceeded']], 429, ['Retry-After' => '30']);
            }

            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                $requestCounts['gemini']++;

                return Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'transcript' => 'Gemini fallback transcript.',
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);
        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id])
                ->post(route('interview.transcribe'), [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'audio' => UploadedFile::fake()->createWithContent("speech-{$attempt}.webm", str_repeat('0', 2048)),
                ])
                ->assertOk()
                ->assertJson([
                    'transcript' => 'Gemini fallback transcript.',
                    'transcription_source' => 'gemini',
                ]);
        }

        $this->assertSame(1, $requestCounts['openai']);
        $this->assertSame(2, $requestCounts['gemini']);
    }

    public function test_rate_limited_transcription_chain_returns_retry_after_hint(): void
    {
        $this->setEnvValue('OPENAI_API_KEY', 'test-openai-key');
        $this->setEnvValue('GEMINI_API_KEY', '');

        config([
            'services.ai_transcription.provider_priority' => 'openai',
            'services.openai.transcription_model' => 'gpt-transcribe',
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'error' => ['message' => 'quota exceeded'],
            ], 429, ['Retry-After' => '23']),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'error_code' => 'speech_transcription_rate_limited',
                'transcription_status' => 'failed',
            ]);

        $retryAfter = (int) $response->json('retry_after_seconds');
        $this->assertGreaterThanOrEqual(20, $retryAfter);
        $this->assertLessThanOrEqual(23, $retryAfter);
        $this->assertSame((string) $retryAfter, $response->headers->get('Retry-After'));
    }

    public function test_transcription_reports_unavailable_when_no_provider_is_configured(): void
    {
        $this->setEnvValue('OPENAI_API_KEY', '');
        $this->setEnvValue('GEMINI_API_KEY', '');

        config([
            'services.ai_transcription.provider_priority' => 'openai,gemini',
            'services.local_speech.enabled' => false,
        ]);

        Http::fake();

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertStatus(503)
            ->assertJson([
                'error_code' => 'speech_transcription_unavailable',
                'transcription_status' => 'unavailable',
            ]);

        Http::assertNothingSent();
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

    public function test_user_cannot_transcribe_audio_for_another_sessions_question(): void
    {
        Http::fake();

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $otherSession = $this->sessionFor($otherUser, $category);
        $otherQuestion = $this->sessionQuestion($otherSession, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $otherQuestion->id,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    private function category(): Category
    {
        return Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
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

    private function sessionQuestion(InterviewSession $session, Category $category): Question
    {
        return Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a difficult project.',
            'difficulty' => $session->difficulty,
            'status' => 'active',
        ]);
    }
}
