<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\AiProvider;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
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
            $this->assertSame('ai_adapted_source_backed', $first->json('source_type'));
            $this->assertStringNotContainsStringIgnoringCase('you mentioned', $first->json('next_question_text'));
            $this->assertStringContainsString('approval queue', strtolower($first->json('next_question_text')));
            $this->assertStringNotContainsString('space rocket', strtolower($first->json('next_question_text')));
            $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
            $this->assertSame($mode, InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail()->response_mode);
        }

        $this->assertNotEmpty($capturedPrompts);
        $this->assertStringContainsString('Source dataset: Job Interview Questions', $capturedPrompts[0]);
        $this->assertStringContainsString('mandatory grounding for the next question', $capturedPrompts[0]);
        $this->assertStringContainsString('exact wording does not need to appear in the dataset', $capturedPrompts[0]);
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

    public function test_seaman_session_rejects_an_ai_software_follow_up_without_using_local_fallback(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
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
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'ai_interview_provider_failed');

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.openai.com'));
        $this->assertSame(1, Question::where('interview_session_id', $session->id)->count());
        $this->assertDatabaseHas('interview_answers', [
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
        ]);
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
