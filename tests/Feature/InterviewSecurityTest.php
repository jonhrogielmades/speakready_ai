<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AiProvider;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InterviewSecurityTest extends TestCase
{
    use RefreshDatabase;

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
                'audio' => UploadedFile::fake()->create('speech.webm', 32, 'audio/webm'),
            ])
            ->assertOk()
            ->assertJson(['transcript' => 'I delivered a stable migration.']);

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
                && in_array('STAR method', (array) data_get($fields, 'keywords[]', []), true)
                && str_contains((string) data_get($fields, 'prompt.0'), 'Philippine job interview practice answer')
                && ($filePart['filename'] ?? null) === 'speech.webm';
        });
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
