<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Category;
use App\Models\GameLevel;
use App\Models\GameSession;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
use App\Services\AIService;
use App\Services\LocalSpeechAssessmentService;
use App\Support\InterviewerAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

    public function test_ai_coach_generates_possible_answer_for_coaching_session_question(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'During my capstone project, I faced [brief challenge]. My responsibility was to [your task], so I [specific action]. The result was [result or lesson], and I would use that ownership in the Developer role.',
                        ],
                    ],
                ],
            ]),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, [
            'ai_assistance_level' => 'beginner',
            'live_feedback_mode' => 'coaching',
        ]);
        $question = $this->sessionQuestion($session, $category);
        $question->update([
            'type' => 'Behavioral',
            'expected_guide' => 'Use situation, task, action, and result.',
            'mapped_skills' => ['ownership', 'problem solving'],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.coachAnswer'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('question_id', $question->id)
            ->assertJsonPath('source', 'ai')
            ->assertJsonPath('provider', 'openai');

        $this->assertStringContainsString('During my capstone project', $response->json('possible_answer'));
        $this->assertDatabaseCount('interview_answers', 0);

        Http::assertSent(function ($request) use ($question) {
            $prompt = data_get($request->data(), 'messages.1.content', '');

            return str_contains($prompt, 'Create one possible interview answer')
                && str_contains($prompt, $question->question_text)
                && str_contains($prompt, 'ai_assistance_level');
        });
    }

    public function test_ai_coach_possible_answer_is_blocked_in_real_interview_mode(): void
    {
        Http::fake();

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, [
            'live_feedback_mode' => 'real_interview',
        ]);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.coachAnswer'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ])
            ->assertForbidden()
            ->assertJsonPath('error', 'AI Coach is available only when Coaching On is selected.');

        Http::assertNothingSent();
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

    public function test_legacy_pinned_openai_voice_remains_available_for_active_question(): void
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

        $session->forceFill(['tts_provider' => 'openai', 'tts_model' => 'gpt-4o-mini-tts', 'tts_voice' => 'nova'])->save();

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
            && $request['voice'] === 'nova'
            && str_contains((string) $request['instructions'], 'female interviewer')
            && $request['input'] === $question->question_text);
    }

    public function test_legacy_pinned_openai_voice_remains_available_for_closing_text(): void
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

        $session->forceFill(['tts_provider' => 'openai', 'tts_model' => 'gpt-4o-mini-tts', 'tts_voice' => 'nova'])->save();

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
            && $request['voice'] === 'nova'
            && str_contains((string) $request['instructions'], 'female interviewer')
            && $request['input'] === $closingText);
    }

    public function test_user_can_request_speech_with_gemini_tts_provider(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'gemini',
            'services.openai.api_key' => '',
            'services.elevenlabs.api_key' => '',
            'services.gemini.tts_model' => 'gemini-3.1-flash-tts-preview',
            'services.gemini.tts_voice' => 'Leda',
            'services.gemini.tts_style' => 'Say clearly',
        ]);
        $this->setEnvValue('OPENAI_API_KEY', '');

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

        $this->assertTrue(AIService::interviewerSpeechAvailable());

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
            && data_get($request->data(), 'generation_config.speech_config.0.voice') === 'Sulafat'
            && str_contains((string) $request['input'], 'professional woman interviewer')
            && str_contains((string) $request['input'], $question->question_text));
    }

    public function test_legacy_pinned_elevenlabs_voice_remains_available(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'elevenlabs',
            'services.elevenlabs.api_key' => 'sk_test-eleven-key',
            'services.elevenlabs.api_endpoint' => 'https://api.elevenlabs.io/v1',
            'services.elevenlabs.tts_model' => 'eleven_multilingual_v2',
            'services.elevenlabs.tts_voice_id' => '21m00Tcm4TlvDq8ikWAM',
            'services.elevenlabs.tts_output_format' => 'mp3_44100_128',
            'services.elevenlabs.tts_language_code' => '',
            'services.elevenlabs.tts_stability' => 0.45,
            'services.elevenlabs.tts_similarity_boost' => 0.75,
            'services.elevenlabs.tts_style' => 0.15,
            'services.elevenlabs.tts_speed' => 0.90,
            'services.elevenlabs.tts_speaker_boost' => true,
        ]);

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128' => Http::response('eleven-audio', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = $this->sessionQuestion($session, $category);

        $session->forceFill([
            'tts_provider' => 'elevenlabs',
            'tts_model' => 'eleven_multilingual_v2',
            'tts_voice' => '21m00Tcm4TlvDq8ikWAM',
        ])->save();

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('eleven-audio');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128'
            && $request->hasHeader('xi-api-key', 'sk_test-eleven-key')
            && $request['text'] === $question->question_text
            && $request['model_id'] === 'eleven_multilingual_v2'
            && data_get($request->data(), 'voice_settings.stability') === 0.45
            && data_get($request->data(), 'voice_settings.speed') === 0.90
            && data_get($request->data(), 'voice_settings.use_speaker_boost') === true);
    }

    public function test_generic_tts_provider_priority_falls_back_when_primary_provider_fails(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'elevenlabs,openai,gemini',
            'services.openai.tts_enabled' => true,
            'services.elevenlabs.api_key' => 'sk_test-eleven-key',
            'services.elevenlabs.api_endpoint' => 'https://api.elevenlabs.io/v1',
            'services.elevenlabs.tts_model' => 'eleven_multilingual_v2',
            'services.elevenlabs.tts_voice_id' => '21m00Tcm4TlvDq8ikWAM',
            'services.elevenlabs.tts_output_format' => 'mp3_44100_128',
            'services.elevenlabs.tts_speed' => 0.90,
        ]);

        Http::fake([
            'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128' => Http::response('eleven-failed', 500),
            'https://api.openai.com/v1/audio/speech' => Http::response('openai-backup-audio', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $speech = AIService::synthesizeSpeech('Describe a difficult project.');
        $this->assertSame('openai-backup-audio', $speech['audio']);
        $this->assertSame('openai', $speech['provider']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128');
        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'
            && $request['input'] === 'Describe a difficult project.'
            && $request['voice'] === 'nova');
    }

    public function test_interview_speech_pins_first_successful_provider_model_and_voice(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai,elevenlabs',
            'services.gemini.tts_model' => 'gemini-3.1-flash-tts-preview',
            'services.openai.api_key' => '',
            'services.elevenlabs.api_key' => '',
        ]);
        $this->setEnvValue('OPENAI_API_KEY', '');

        $geminiRequests = [];
        Http::fake(function ($request) use (&$geminiRequests) {
            $geminiRequests[] = $request;

            return count($geminiRequests) === 3
                ? Http::response(['error' => 'unavailable'], 503)
                : Http::response(['interaction' => ['output_audio' => ['data' => base64_encode(str_repeat("\0", 480))]]], 200);
        });

        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['interviewer_avatar' => 'hiring_manager']);
        $question = $this->sessionQuestion($session, $category);
        $speechRequest = [
            'session_id' => $session->id,
            'question_id' => $question->id,
        ];

        $first = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), $speechRequest)
            ->assertOk();
        $this->assertStringStartsWith('RIFF', $first->getContent());

        $session->refresh();
        $this->assertSame('gemini', $session->tts_provider);
        $this->assertSame('gemini-3.1-flash-tts-preview', $session->tts_model);
        $this->assertSame('Orus', $session->tts_voice);

        config([
            'services.gemini.tts_model' => 'gemini-3.8-flash-tts',
            'services.gemini.tts_voice' => 'Leda',
        ]);

        $this->post(route('interview.speech'), $speechRequest)
            ->assertOk();
        $this->post(route('interview.speech'), $speechRequest)
            ->assertStatus(503);

        $this->assertCount(3, $geminiRequests);
        foreach ($geminiRequests as $request) {
            $this->assertSame('https://generativelanguage.googleapis.com/v1beta/interactions', $request->url());
            $this->assertSame('gemini-3.1-flash-tts-preview', $request['model']);
            $this->assertSame('Orus', data_get($request->data(), 'generation_config.speech_config.0.voice'));
            $this->assertStringContainsString('professional man interviewer', (string) $request['input']);
        }
    }

    public function test_a_gemini_pinned_session_keeps_its_voice_after_a_rate_limit(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'gemini',
            'services.openai.tts_enabled' => true,
            'services.gemini.tts_model' => 'gemini-3.1-flash-tts-preview',
            'services.openai.tts_model' => 'gpt-4o-mini-tts',
        ]);

        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $geminiRequests = [];
        $openAiRequests = [];
        Http::fake(function ($request) use (&$geminiRequests, &$openAiRequests) {
            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/interactions') {
                $geminiRequests[] = $request;

                return count($geminiRequests) === 1
                    ? Http::response(['interaction' => ['output_audio' => ['data' => base64_encode(str_repeat("\0", 480))]]], 200)
                    : Http::response(['error' => ['message' => 'Gemini quota exhausted']], 429);
            }

            $openAiRequests[] = $request;

            return Http::response('openai-pinned-audio', 200, ['Content-Type' => 'audio/mpeg']);
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['interviewer_avatar' => 'role_specialist']);
        $question = $this->sessionQuestion($session, $category);
        $request = ['session_id' => $session->id, 'question_id' => $question->id];
        $session->forceFill([
            'tts_provider' => 'gemini',
            'tts_model' => 'gemini-3.1-flash-tts-preview',
            'tts_voice' => 'Iapetus',
        ])->save();

        $this->actingAs($user)->withSession(['active_interview_id' => $session->id]);
        $this->post(route('interview.speech'), $request)
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/wav');
        $session->refresh();
        $this->assertSame('gemini', $session->tts_provider);
        $this->assertSame('gemini-3.1-flash-tts-preview', $session->tts_model);
        $this->assertSame('Iapetus', $session->tts_voice);

        config(['services.gemini.tts_model' => 'gemini-3.8-flash-tts']);
        $this->post(route('interview.speech'), $request)
            ->assertStatus(503)
            ->assertJsonPath('error', 'AI speech is not available.');
        $session->refresh();
        $this->assertSame('gemini', $session->tts_provider);
        $this->assertSame('gemini-3.1-flash-tts-preview', $session->tts_model);
        $this->assertSame('Iapetus', $session->tts_voice);

        $this->post(route('interview.speech'), $request)
            ->assertStatus(503);

        $this->assertCount(3, $geminiRequests);
        $this->assertCount(0, $openAiRequests, 'A pinned Gemini session must not use another interviewer voice.');
        foreach ($geminiRequests as $geminiRequest) {
            $this->assertSame('gemini-3.1-flash-tts-preview', $geminiRequest['model']);
            $this->assertSame('Iapetus', data_get($geminiRequest->data(), 'generation_config.speech_config.0.voice'));
        }
    }

    public function test_avatar_voice_preview_uses_six_distinct_gemini_voices_and_fixed_text(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai',
            'services.openai.api_key' => '',
            'services.elevenlabs.api_key' => '',
            'services.gemini.tts_model' => 'gemini-3.1-flash-tts-preview',
        ]);
        $this->setEnvValue('OPENAI_API_KEY', '');

        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            return Http::response(['interaction' => ['output_audio' => ['data' => base64_encode(str_repeat("\0", 480))]]], 200);
        });

        $expected = [
            'friendly_recruiter' => ['woman', 'Sulafat'],
            'structured_hr' => ['woman', 'Kore'],
            'senior_leader' => ['woman', 'Gacrux'],
            'hiring_manager' => ['man', 'Orus'],
            'role_specialist' => ['man', 'Iapetus'],
            'panel_interviewer' => ['man', 'Algenib'],
        ];
        $this->assertEqualsCanonicalizing(array_keys($expected), InterviewerAvatar::keys());
        $this->assertCount(6, array_unique(array_column($expected, 1)));

        $this->actingAs(User::factory()->create(['is_admin' => false, 'status' => 'active']));
        foreach ($expected as $avatar => [$gender, $voice]) {
            $this->assertSame($gender, InterviewerAvatar::gender($avatar));
            $this->assertSame($voice, InterviewerAvatar::voice($avatar));

            $response = $this->postJson(route('interview.avatarVoicePreview'), ['interviewer_avatar' => $avatar]);
            $response->assertOk()->assertHeader('Content-Type', 'audio/wav');
            $this->assertStringStartsWith('RIFF', $response->getContent());
        }

        $this->assertCount(6, $requests);
        foreach (array_keys($expected) as $index => $avatar) {
            $request = $requests[$index];
            $this->assertSame('https://generativelanguage.googleapis.com/v1beta/interactions', $request->url());
            $this->assertSame($expected[$avatar][1], data_get($request->data(), 'generation_config.speech_config.0.voice'));
            $this->assertStringContainsString('Tell me about yourself.', (string) $request['input']);
            $this->assertStringContainsString('professional '.$expected[$avatar][0].' interviewer', (string) $request['input']);
        }
    }

    public function test_interview_speech_prefers_openai_and_pins_the_selected_avatar_voice(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'gemini',
            'services.openai.tts_enabled' => true,
            'services.gemini.tts_model' => 'gemini-3.1-flash-tts-preview',
            'services.openai.tts_model' => 'gpt-4o-mini-tts',
        ]);

        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/interactions') {
                return Http::response(['error' => ['message' => 'Gemini quota exhausted']], 429);
            }

            return Http::response('openai-fallback-audio', 200, ['Content-Type' => 'audio/mpeg']);
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, [
            'interviewer_avatar' => 'hiring_manager',
            'num_questions' => 2,
        ]);
        $firstQuestion = $this->sessionQuestion($session, $category);
        $secondQuestion = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'How did you measure the result?',
            'difficulty' => $session->difficulty,
            'status' => 'active',
        ]);

        $this->actingAs($user)->withSession(['active_interview_id' => $session->id]);
        foreach ([$firstQuestion, $secondQuestion] as $question) {
            $this->post(route('interview.speech'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
            ])
                ->assertOk()
                ->assertHeader('Content-Type', 'audio/mpeg')
                ->assertSee('openai-fallback-audio');
        }
        $closingText = 'Thank you for your answers. This concludes our interview.';
        $this->post(route('interview.speech'), [
            'session_id' => $session->id,
            'speech_text' => $closingText,
        ])->assertOk()->assertHeader('Content-Type', 'audio/mpeg');

        $session->refresh();
        $this->assertSame('openai', $session->tts_provider);
        $this->assertSame('gpt-4o-mini-tts', $session->tts_model);
        $this->assertSame('onyx', $session->tts_voice);

        $geminiRequests = array_values(array_filter($requests, fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com')));
        $openAiRequests = array_values(array_filter($requests, fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'));
        $this->assertCount(0, $geminiRequests, 'An available OpenAI voice should be used before Gemini.');
        $this->assertCount(3, $openAiRequests);
        $this->assertSame([$firstQuestion->question_text, $secondQuestion->question_text, $closingText], array_map(fn ($request) => $request['input'], $openAiRequests));
        foreach ($openAiRequests as $request) {
            $this->assertSame('gpt-4o-mini-tts', $request['model']);
            $this->assertSame('onyx', $request['voice']);
            $this->assertStringContainsString('male interviewer', (string) $request['instructions']);
        }
    }

    public function test_avatar_voice_preview_prefers_six_distinct_openai_voices(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'gemini',
            'services.openai.tts_enabled' => true,
        ]);

        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/interactions') {
                return Http::response(['error' => ['message' => 'Gemini quota exhausted']], 429);
            }

            return Http::response('openai-preview-audio', 200, ['Content-Type' => 'audio/mpeg']);
        });

        $expected = [
            'friendly_recruiter' => ['woman', 'nova'],
            'structured_hr' => ['woman', 'coral'],
            'senior_leader' => ['woman', 'shimmer'],
            'hiring_manager' => ['man', 'onyx'],
            'role_specialist' => ['man', 'echo'],
            'panel_interviewer' => ['man', 'ash'],
        ];
        $this->assertEqualsCanonicalizing(array_keys($expected), InterviewerAvatar::keys());
        $this->assertCount(6, array_unique(array_column($expected, 1)));

        $this->actingAs(User::factory()->create(['is_admin' => false, 'status' => 'active']));
        foreach ($expected as $avatar => [$gender, $voice]) {
            $this->assertSame($voice, InterviewerAvatar::openAiVoice($avatar));

            $this->postJson(route('interview.avatarVoicePreview'), ['interviewer_avatar' => $avatar])
                ->assertOk()
                ->assertHeader('Content-Type', 'audio/mpeg')
                ->assertSee('openai-preview-audio');
        }

        $geminiRequests = array_values(array_filter($requests, fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com')));
        $openAiRequests = array_values(array_filter($requests, fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'));
        $this->assertCount(0, $geminiRequests, 'Preview should not call Gemini when OpenAI succeeds.');
        $this->assertCount(6, $openAiRequests);
        foreach (array_values($expected) as $index => [$gender, $voice]) {
            $request = $openAiRequests[$index];
            $this->assertSame($voice, $request['voice']);
            $this->assertSame('Hello. I am your AI interviewer. Tell me about yourself.', $request['input']);
            $this->assertStringContainsString($gender === 'woman' ? 'female interviewer' : 'male interviewer', (string) $request['instructions']);
        }
    }

    public function test_new_interview_session_falls_back_through_all_three_speech_providers_and_pins_elevenlabs(): void
    {
        $this->configureInterviewerSpeechProviderChain();

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            if ($request->url() === 'https://api.openai.com/v1/audio/speech') {
                return Http::response(['error' => ['message' => 'OpenAI rate limit']], 429);
            }
            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/interactions') {
                return Http::response(['error' => ['message' => 'Gemini rate limit']], 429);
            }

            return Http::response('elevenlabs-backup-audio', 200, ['Content-Type' => 'audio/mpeg']);
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['interviewer_avatar' => 'hiring_manager']);
        $question = $this->sessionQuestion($session, $category);
        $payload = ['session_id' => $session->id, 'question_id' => $question->id];

        $this->actingAs($user)->withSession(['active_interview_id' => $session->id]);
        $this->post(route('interview.speech'), $payload)
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('elevenlabs-backup-audio');

        $session->refresh();
        $this->assertSame('elevenlabs', $session->tts_provider);
        $this->assertSame('eleven_multilingual_v2', $session->tts_model);
        $this->assertSame('21m00Tcm4TlvDq8ikWAM', $session->tts_voice);

        $this->post(route('interview.speech'), $payload)->assertOk();

        $this->assertSame([
            'https://api.openai.com/v1/audio/speech',
            'https://generativelanguage.googleapis.com/v1beta/interactions',
            'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128',
            'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128',
        ], array_map(fn ($request) => $request->url(), $requests));
        $this->assertSame('onyx', $requests[0]['voice']);
        $this->assertSame('Orus', data_get($requests[1]->data(), 'generation_config.speech_config.0.voice'));
        $this->assertSame($question->question_text, $requests[2]['text']);
    }

    public function test_concurrent_first_speech_request_returns_only_audio_matching_the_winning_voice(): void
    {
        $this->configureInterviewerSpeechProviderChain();

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['interviewer_avatar' => 'role_specialist']);
        $question = $this->sessionQuestion($session, $category);

        $requests = [];
        Http::fake(function ($request) use (&$requests, $session) {
            $requests[] = $request;

            if ($request->url() === 'https://api.openai.com/v1/audio/speech') {
                // Another request pins Gemini while the first OpenAI audio is in flight.
                InterviewSession::query()->whereKey($session->id)->whereNull('tts_provider')->update([
                    'tts_provider' => 'gemini',
                    'tts_model' => 'gemini-3.1-flash-tts-preview',
                    'tts_voice' => 'Iapetus',
                ]);

                return Http::response('losing-openai-audio', 200, ['Content-Type' => 'audio/mpeg']);
            }

            return Http::response([
                'interaction' => ['output_audio' => ['data' => base64_encode(str_repeat("\0", 480))]],
            ], 200);
        });

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), ['session_id' => $session->id, 'question_id' => $question->id])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/wav');

        $this->assertStringStartsWith('RIFF', $response->getContent());
        $this->assertStringNotContainsString('losing-openai-audio', $response->getContent());
        $session->refresh();
        $this->assertSame('gemini', $session->tts_provider);
        $this->assertSame('gemini-3.1-flash-tts-preview', $session->tts_model);
        $this->assertSame('Iapetus', $session->tts_voice);
        $this->assertSame([
            'https://api.openai.com/v1/audio/speech',
            'https://generativelanguage.googleapis.com/v1beta/interactions',
        ], array_map(fn ($request) => $request->url(), $requests));
        $this->assertSame('echo', $requests[0]['voice']);
        $this->assertSame('Iapetus', data_get($requests[1]->data(), 'generation_config.speech_config.0.voice'));
    }

    public function test_openai_pinned_session_does_not_switch_to_gemini_when_openai_reaches_its_limit(): void
    {
        $this->configureInterviewerSpeechProviderChain();

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            if ($request->url() === 'https://api.openai.com/v1/audio/speech') {
                return Http::response(['error' => ['message' => 'OpenAI rate limit']], 429);
            }

            return Http::response([
                'interaction' => ['output_audio' => ['data' => base64_encode(str_repeat("\0", 480))]],
            ], 200);
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['interviewer_avatar' => 'role_specialist']);
        $question = $this->sessionQuestion($session, $category);
        $session->forceFill([
            'tts_provider' => 'openai',
            'tts_model' => 'gpt-4o-mini-tts',
            'tts_voice' => 'echo',
        ])->save();
        $payload = ['session_id' => $session->id, 'question_id' => $question->id];

        $this->actingAs($user)->withSession(['active_interview_id' => $session->id]);
        $this->post(route('interview.speech'), $payload)
            ->assertStatus(503)
            ->assertJsonPath('error', 'AI speech is not available.');

        $session->refresh();
        $this->assertSame('openai', $session->tts_provider);
        $this->assertSame('gpt-4o-mini-tts', $session->tts_model);
        $this->assertSame('echo', $session->tts_voice);

        config(['services.gemini.tts_model' => 'gemini-new-default-model']);
        $this->post(route('interview.speech'), $payload)->assertStatus(503);

        $this->assertSame([
            'https://api.openai.com/v1/audio/speech',
            'https://api.openai.com/v1/audio/speech',
        ], array_map(fn ($request) => $request->url(), $requests));
        foreach ($requests as $request) {
            $this->assertSame('echo', $request['voice']);
            $this->assertSame('gpt-4o-mini-tts', $request['model']);
        }
    }

    public function test_gemini_pinned_session_does_not_switch_to_elevenlabs_after_gemini_fails(): void
    {
        $this->configureInterviewerSpeechProviderChain();

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            if ($request->url() === 'https://generativelanguage.googleapis.com/v1beta/interactions'
                || $request->url() === 'https://api.openai.com/v1/audio/speech') {
                return Http::response(['error' => ['message' => 'Provider unavailable']], 503);
            }

            return Http::response('elevenlabs-pinned-audio', 200, ['Content-Type' => 'audio/mpeg']);
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['interviewer_avatar' => 'senior_leader']);
        $question = $this->sessionQuestion($session, $category);
        $session->forceFill([
            'tts_provider' => 'gemini',
            'tts_model' => 'gemini-3.1-flash-tts-preview',
            'tts_voice' => 'Gacrux',
        ])->save();
        $payload = ['session_id' => $session->id, 'question_id' => $question->id];

        $this->actingAs($user)->withSession(['active_interview_id' => $session->id]);
        $this->post(route('interview.speech'), $payload)
            ->assertStatus(503)
            ->assertJsonPath('error', 'AI speech is not available.');

        $session->refresh();
        $this->assertSame('gemini', $session->tts_provider);
        $this->assertSame('gemini-3.1-flash-tts-preview', $session->tts_model);
        $this->assertSame('Gacrux', $session->tts_voice);

        $this->post(route('interview.speech'), $payload)->assertStatus(503);
        $this->assertSame([
            'https://generativelanguage.googleapis.com/v1beta/interactions',
            'https://generativelanguage.googleapis.com/v1beta/interactions',
        ], array_map(fn ($request) => $request->url(), $requests));
        foreach ($requests as $request) {
            $this->assertSame('gemini-3.1-flash-tts-preview', $request['model']);
            $this->assertSame('Gacrux', data_get($request->data(), 'generation_config.speech_config.0.voice'));
        }
    }

    public function test_elevenlabs_pinned_session_does_not_switch_to_another_voice_when_it_fails(): void
    {
        $this->configureInterviewerSpeechProviderChain();

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            return Http::response(['detail' => ['message' => 'ElevenLabs quota exhausted']], 429);
        });

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['interviewer_avatar' => 'panel_interviewer']);
        $question = $this->sessionQuestion($session, $category);
        $session->forceFill([
            'tts_provider' => 'elevenlabs',
            'tts_model' => 'eleven_multilingual_v2',
            'tts_voice' => '21m00Tcm4TlvDq8ikWAM',
        ])->save();

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.speech'), ['session_id' => $session->id, 'question_id' => $question->id])
            ->assertStatus(503)
            ->assertJsonPath('error', 'AI speech is not available.');

        $session->refresh();
        $this->assertSame('elevenlabs', $session->tts_provider);
        $this->assertSame('eleven_multilingual_v2', $session->tts_model);
        $this->assertSame('21m00Tcm4TlvDq8ikWAM', $session->tts_voice);
        $this->assertCount(1, $requests);
        $this->assertSame(
            'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128',
            $requests[0]->url()
        );
    }

    public function test_avatar_voice_preview_reaches_elevenlabs_after_openai_and_gemini_fail(): void
    {
        $this->configureInterviewerSpeechProviderChain();

        $requests = [];
        Http::fake(function ($request) use (&$requests) {
            $requests[] = $request;

            if (count($requests) <= 2) {
                return Http::response(['error' => ['message' => 'Provider rate limit']], 429);
            }

            return Http::response('elevenlabs-preview-audio', 200, ['Content-Type' => 'audio/mpeg']);
        });

        $this->actingAs(User::factory()->create(['is_admin' => false, 'status' => 'active']))
            ->postJson(route('interview.avatarVoicePreview'), ['interviewer_avatar' => 'panel_interviewer'])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('elevenlabs-preview-audio');

        $this->assertSame([
            'https://api.openai.com/v1/audio/speech',
            'https://generativelanguage.googleapis.com/v1beta/interactions',
            'https://api.elevenlabs.io/v1/text-to-speech/21m00Tcm4TlvDq8ikWAM?output_format=mp3_44100_128',
        ], array_map(fn ($request) => $request->url(), $requests));
        $this->assertSame('ash', $requests[0]['voice']);
        $this->assertSame('Algenib', data_get($requests[1]->data(), 'generation_config.speech_config.0.voice'));
        $this->assertSame('Hello. I am your AI interviewer. Tell me about yourself.', $requests[2]['text']);
    }

    public function test_openai_only_credentials_keep_interviewer_speech_available(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'gemini',
            'services.openai.tts_enabled' => true,
            'services.gemini.api_key' => '',
        ]);
        $this->setEnvValue('GEMINI_API_KEY', '');

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);
        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::response('openai-only-preview', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
        ]);

        $this->assertTrue(AIService::interviewerSpeechAvailable());
        $this->assertFalse(AIService::interviewerSpeechAvailable('gemini'));
        $this->assertTrue(AIService::interviewerSpeechAvailable('openai'));

        $this->actingAs(User::factory()->create(['is_admin' => false, 'status' => 'active']))
            ->postJson(route('interview.avatarVoicePreview'), ['interviewer_avatar' => 'panel_interviewer'])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertSee('openai-only-preview');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'
            && $request['voice'] === 'ash');
    }

    public function test_avatar_voice_preview_requires_auth_and_valid_key_and_fails_cleanly_when_unavailable(): void
    {
        Http::fake();

        $this->postJson(route('interview.avatarVoicePreview'), ['interviewer_avatar' => 'friendly_recruiter'])
            ->assertUnauthorized();

        $this->actingAs(User::factory()->create(['is_admin' => false, 'status' => 'active']));
        $this->postJson(route('interview.avatarVoicePreview'), ['interviewer_avatar' => 'unknown'])
            ->assertUnprocessable();
        $this->postJson(route('interview.avatarVoicePreview'), [
            'interviewer_avatar' => 'friendly_recruiter',
            'speech_text' => 'Say a user supplied script',
        ])->assertUnprocessable();

        config(['services.ai_tts.enabled' => false]);
        $this->assertFalse(AIService::interviewerSpeechAvailable());
        $this->postJson(route('interview.avatarVoicePreview'), ['interviewer_avatar' => 'friendly_recruiter'])
            ->assertStatus(503)
            ->assertJsonPath('error', 'AI voice preview is not available.');

        Http::assertNothingSent();
    }

    public function test_user_can_request_onboarding_speech_from_tts_providers(): void
    {
        Storage::fake('local');
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai',
            'services.ai_tts.onboarding_storage_disk' => 'local',
            'services.openai.tts_enabled' => true,
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::response('onboarding-audio', 200, [
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

        $firstResponse = $this->actingAs($user)
            ->post(route('user.onboarding.speech'), [
                'speech_text' => 'Welcome to your dashboard tutorial.',
                'language' => 'en',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-SpeakReady-Voice-Cache', 'MISS')
            ->assertHeader('X-SpeakReady-Voice-Source', 'PROVIDER')
            ->assertSee('onboarding-audio');

        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), [
                'speech_text' => 'Welcome   to your dashboard tutorial.',
                'language' => 'en',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-SpeakReady-Voice-Cache', 'HIT')
            ->assertHeader('X-SpeakReady-Voice-Source', 'CACHE')
            ->assertSee('onboarding-audio');

        Cache::flush();

        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), [
                'speech_text' => 'Welcome to your dashboard tutorial.',
                'language' => 'en',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-SpeakReady-Voice-Cache', 'HIT')
            ->assertHeader('X-SpeakReady-Voice-Source', 'SAVED')
            ->assertSee('onboarding-audio');

        config([
            'services.ai_tts.enabled' => false,
            'services.openai.tts_enabled' => false,
        ]);
        Cache::flush();

        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), [
                'speech_text' => 'Welcome to your dashboard tutorial.',
                'language' => 'en',
            ])
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'HIT')
            ->assertHeader('X-SpeakReady-Voice-Source', 'SAVED_FALLBACK')
            ->assertSee('onboarding-audio');

        $this->assertStringContainsString('private', (string) $firstResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $firstResponse->headers->get('Cache-Control'));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'
            && $request['input'] === 'Welcome to your dashboard tutorial.'
            && $request['voice'] === 'nova');
        Http::assertSentCount(1);
    }

    public function test_failed_onboarding_speech_uses_a_short_cooldown_without_caching_audio(): void
    {
        Storage::fake('local');
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai',
            'services.ai_tts.onboarding_storage_disk' => 'local',
            'services.openai.tts_enabled' => true,
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::sequence()
                ->push(['error' => ['message' => 'Temporary provider failure']], 503)
                ->push('recovered-onboarding-audio', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $payload = ['speech_text' => 'Retry this tutorial line.', 'language' => 'en'];

        $this->actingAs($user)
            ->postJson(route('user.onboarding.speech'), $payload)
            ->assertStatus(503);

        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->actingAs($user)
            ->postJson(route('user.onboarding.speech'), $payload)
            ->assertStatus(503);

        Http::assertSentCount(1);
        Cache::flush();

        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), $payload)
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'MISS')
            ->assertHeader('X-SpeakReady-Voice-Source', 'PROVIDER')
            ->assertSee('recovered-onboarding-audio');

        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), $payload)
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'HIT')
            ->assertHeader('X-SpeakReady-Voice-Source', 'CACHE');

        Cache::flush();

        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), $payload)
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'HIT')
            ->assertHeader('X-SpeakReady-Voice-Source', 'SAVED')
            ->assertSee('recovered-onboarding-audio');

        Http::assertSentCount(2);
    }

    public function test_onboarding_speech_cache_is_separated_by_language_and_voice(): void
    {
        Storage::fake('local');
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai',
            'services.ai_tts.onboarding_storage_disk' => 'local',
            'services.openai.tts_enabled' => true,
            'services.openai.tts_voice' => 'nova',
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::sequence()
                ->push('english-onboarding-audio', 200, ['Content-Type' => 'audio/mpeg'])
                ->push('filipino-onboarding-audio', 200, ['Content-Type' => 'audio/mpeg']),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'preferred_language' => 'en',
        ]);
        $payload = ['speech_text' => 'Your next tutorial step.', 'language' => 'en'];

        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), $payload)
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'MISS')
            ->assertHeader('X-SpeakReady-Voice-Source', 'PROVIDER')
            ->assertSee('english-onboarding-audio');

        $user->update(['preferred_language' => 'fil']);
        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), $payload)
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'MISS')
            ->assertHeader('X-SpeakReady-Voice-Source', 'PROVIDER')
            ->assertSee('filipino-onboarding-audio');

        config(['services.openai.tts_voice' => 'shimmer']);
        Cache::flush();
        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), $payload)
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'HIT')
            ->assertHeader('X-SpeakReady-Voice-Source', 'SAVED_FALLBACK')
            ->assertSee('filipino-onboarding-audio');

        $user->update(['preferred_language' => 'en']);
        Cache::flush();
        $this->actingAs($user)
            ->post(route('user.onboarding.speech'), $payload)
            ->assertOk()
            ->assertHeader('X-SpeakReady-Voice-Cache', 'HIT')
            ->assertHeader('X-SpeakReady-Voice-Source', 'SAVED_FALLBACK')
            ->assertSee('english-onboarding-audio');

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['voice'] === 'nova'
            && str_contains((string) $request['instructions'], 'natural English pronunciation'));
        Http::assertSent(fn ($request) => $request['voice'] === 'nova'
            && str_contains((string) $request['instructions'], 'natural Filipino pronunciation'));
        Http::assertNotSent(fn ($request) => $request['voice'] === 'shimmer');
    }

    public function test_user_can_request_speech_for_their_active_learning_game(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai',
            'services.openai.tts_enabled' => true,
        ]);

        Http::fake([
            'https://api.openai.com/v1/audio/speech' => Http::response('game-audio', 200, [
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
        $category->update(['type' => 'game']);
        $level = GameLevel::create([
            'category_id' => $category->id,
            'level_number' => 1,
            'title' => 'Speech Challenge',
            'description' => 'Practice a spoken response.',
            'mission_text' => 'Tell me about yourself.',
            'target_position' => 'Developer',
            'difficulty' => 'beginner',
            'required_score' => 80,
            'xp_reward' => 100,
            'energy_cost' => 1,
            'is_hidden' => false,
        ]);
        $session = GameSession::create([
            'user_id' => $user->id,
            'game_level_id' => $level->id,
            'status' => 'in_progress',
            'difficulty' => 'beginner',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'response_mode' => 'hybrid',
            'questions' => [['question_text' => 'Tell me about yourself.']],
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('user.game.speech'), [
                'game_session_id' => $session->id,
                'speech_text' => 'Tell me about yourself.',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('game-audio');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/speech'
            && $request['input'] === 'Tell me about yourself.'
            && $request['voice'] === 'nova');
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
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['num_questions' => 1]);
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
                && in_array('Developer', (array) data_get($fields, 'keywords[]', []), true)
                && ! in_array('tabang', (array) data_get($fields, 'keywords[]', []), true)
                && str_contains((string) data_get($fields, 'prompt.0'), 'Job interview practice')
                && str_contains((string) data_get($fields, 'prompt.0'), 'Cebuano')
                && str_contains((string) data_get($fields, 'prompt.0'), 'Recent earlier transcript')
                && ($filePart['filename'] ?? null) === 'speech.webm';
        });
    }

    public function test_cebuano_transcription_uses_auto_detection_instead_of_incorrect_language_hints(): void
    {
        config(['services.openai.transcription_model' => 'gpt-transcribe']);

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => 'Nakatabang ko sa customer ug nasulbad ang problema.',
            ], 200),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'preferred_language' => 'ceb',
        ]);
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
            ->assertJsonPath('transcript', 'Nakatabang ko sa customer ug nasulbad ang problema.');

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://api.openai.com/v1/audio/transcriptions') {
                return false;
            }

            $parts = collect($request->data());
            $prompt = (string) data_get($parts->firstWhere('name', 'prompt'), 'contents', '');

            return $parts->where('name', 'languages[]')->isEmpty()
                && str_contains($prompt, 'Cebuano')
                && str_contains($prompt, 'Bisaya');
        });
    }

    public function test_full_recording_transcription_prefers_ai_transcript_over_local_asr(): void
    {
        config(['services.openai.transcription_model' => 'gpt-transcribe']);

        $this->app->instance(LocalSpeechAssessmentService::class, new class extends LocalSpeechAssessmentService {
            public function assessUploadedAudio(UploadedFile $audioFile, ?string $referenceText = null, array|string|null $targetLanguage = null): array
            {
                return [
                    'version' => self::VERSION,
                    'status' => 'measured',
                    'asr' => [
                        'name' => 'asr',
                        'status' => 'measured',
                        'transcript' => 'local partial duplicate transcript',
                    ],
                    'pronunciation' => [
                        'name' => 'pronunciation',
                        'status' => 'measured',
                        'score' => 82,
                    ],
                    'forced_alignment' => ['name' => 'forced_alignment', 'status' => 'not_measured'],
                    'phoneme_alignment' => ['name' => 'phoneme_alignment', 'status' => 'not_measured'],
                    'gop' => ['name' => 'gop', 'status' => 'not_measured'],
                    'reliability' => [
                        'score' => 88,
                        'band' => 'High',
                        'measured_components' => ['asr', 'pronunciation'],
                    ],
                    'limitations' => [],
                    'recommendations' => [],
                ];
            }
        });

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => 'AI final full recording transcript.',
            ], 200),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['response_mode' => 'hybrid']);
        $question = $this->sessionQuestion($session, $category);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'final_recording' => true,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'transcript' => 'AI final full recording transcript.',
                'transcription_source' => 'openai',
                'transcription_status' => 'transcribed',
            ]);

        $this->assertSame('local partial duplicate transcript', $response->json('pronunciation_analysis.asr.transcript'));
    }

    public function test_high_confidence_local_asr_recovers_an_empty_cloud_transcript(): void
    {
        config([
            'services.openai.transcription_model' => 'gpt-transcribe',
            'services.local_speech.min_transcript_confidence' => 95,
        ]);

        $this->app->instance(LocalSpeechAssessmentService::class, new class extends LocalSpeechAssessmentService {
            public function assessUploadedAudio(UploadedFile $audioFile, ?string $referenceText = null, array|string|null $targetLanguage = null): array
            {
                return [
                    'version' => self::VERSION,
                    'status' => 'measured',
                    'asr' => [
                        'name' => 'asr',
                        'status' => 'measured',
                        'transcript' => 'High confidence local recovery transcript.',
                        'confidence' => 99,
                    ],
                    'pronunciation' => ['name' => 'pronunciation', 'status' => 'not_measured'],
                    'forced_alignment' => ['name' => 'forced_alignment', 'status' => 'not_measured'],
                    'phoneme_alignment' => ['name' => 'phoneme_alignment', 'status' => 'not_measured'],
                    'gop' => ['name' => 'gop', 'status' => 'not_measured'],
                    'reliability' => [
                        'score' => 99,
                        'band' => 'High',
                        'measured_components' => ['asr'],
                    ],
                    'limitations' => [],
                    'recommendations' => [],
                ];
            }
        });

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response(['text' => ''], 200),
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category, ['response_mode' => 'voice']);
        $question = $this->sessionQuestion($session, $category);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->post(route('interview.transcribe'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'final_recording' => true,
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertOk()
            ->assertJson([
                'transcript' => 'High confidence local recovery transcript.',
                'transcription_source' => 'local_speech',
                'transcription_status' => 'transcribed',
                'transcription_verified' => true,
            ])
            ->assertJsonStructure(['transcription_proof']);
    }

    public function test_transcription_response_auto_corrects_common_word_errors(): void
    {
        config(['services.openai.transcription_model' => 'gpt-transcribe']);

        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => 'teh api improovement helped alot because im very very responsable for qa.',
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
                'transcript' => "the API improvement helped a lot because I'm very very responsible for QA.",
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
                        'finishReason' => 'STOP',
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
                            'finishReason' => 'STOP',
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

    private function configureInterviewerSpeechProviderChain(): void
    {
        config([
            'services.ai_tts.enabled' => true,
            'services.ai_tts.provider' => 'openai,gemini,elevenlabs',
            'services.openai.tts_enabled' => true,
            'services.openai.tts_model' => 'gpt-4o-mini-tts',
            'services.gemini.tts_model' => 'gemini-3.1-flash-tts-preview',
            'services.elevenlabs.api_key' => 'sk_test-eleven-key',
            'services.elevenlabs.api_endpoint' => 'https://api.elevenlabs.io/v1',
            'services.elevenlabs.tts_model' => 'eleven_multilingual_v2',
            'services.elevenlabs.tts_voice_id' => '21m00Tcm4TlvDq8ikWAM',
            'services.elevenlabs.tts_output_format' => 'mp3_44100_128',
        ]);

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
