<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AiProvider;
use App\Models\InterviewSession;
use App\Models\Question;
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
        config(['services.openai.tts_enabled' => true]);

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
        config(['services.openai.tts_enabled' => true]);

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

    public function test_speech_endpoint_does_not_call_paid_tts_when_disabled(): void
    {
        config(['services.openai.tts_enabled' => false]);
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

    public function test_user_can_transcribe_audio_for_their_active_question(): void
    {
        config(['services.openai.transcription_model' => 'whisper-1']);

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
            ->assertJson(['transcript' => 'I delivered a stable migration.']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/audio/transcriptions');
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

    private function sessionFor(User $user, Category $category): InterviewSession
    {
        return InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'in_progress',
        ]);
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
