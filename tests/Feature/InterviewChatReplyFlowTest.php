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
        Setting::setVal('int_follow_up', false, 'interview', 'boolean');
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
                ->withSession(['active_interview_id' => $session->id])
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
            $this->assertStringContainsString('approval queue', strtolower($first->json('next_question_text')));
            $this->assertStringNotContainsString('space rocket', strtolower($first->json('next_question_text')));
            $this->assertSame(2, Question::where('interview_session_id', $session->id)->count());
            $this->assertSame($mode, InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail()->response_mode);
        }
    }

    public function test_untranscribed_voice_and_skipped_answers_do_not_invent_candidate_evidence(): void
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
            ->assertOk()
            ->assertJsonPath('success', true);

        $voiceAnswer = InterviewAnswer::where('interview_session_id', $voiceSession->id)->firstOrFail();
        $this->assertSame('', (string) $voiceAnswer->answer_text);
        $this->assertSame('', (string) $voiceAnswer->delivery_transcript);
        $this->assertStringNotContainsString('space rocket', strtolower($voiceResponse->json('next_question_text')));

        [$skippedSession, $skippedQuestion] = $this->interview($user, $category, 'text');
        $skippedResponse = $this->actingAs($user)
            ->withSession(['active_interview_id' => $skippedSession->id])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $skippedSession->id,
                'question_id' => $skippedQuestion->id,
                'response_mode' => 'text',
                'is_skipped' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertStringContainsString('Developer', $skippedResponse->json('next_question_text'));
        $this->assertSame(1, substr_count($skippedResponse->json('next_question_text'), '?'));
    }

    public function test_seaman_session_discards_an_ai_software_follow_up_after_a_lifeboat_answer(): void
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
            ->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.openai.com'));
        $next = Question::findOrFail($response->json('next_question_id'));
        $this->assertSame($next->question_text, $response->json('next_question_text'));
        $this->assertSame('local_question_fallback', $next->source_type);
        $this->assertMatchesRegularExpression(
            '/\b(?:vessel|ship|deck|watchkeeping|navigation|maritime|lifeboat|cargo|bridge|port|crew|safety|emergency)\b/i',
            $next->question_text
        );
        $this->assertDoesNotMatchRegularExpression('/\b(?:cloud|software|deployment|debug)\b/i', $next->question_text);
    }

    public function test_local_conversation_carries_the_previous_result_forward_into_the_next_probe(): void
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
            ])->assertOk()->assertJsonPath('success', true);
        $this->assertStringContainsString('Why did you choose that approach?', $first->json('next_question_text'));

        $second = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $session->id,
                'question_id' => $first->json('next_question_id'),
                'answer_text' => 'I chose that approach because it removed the duplicate approval step.',
            ])->assertOk()->assertJsonPath('success', true);

        $this->assertStringContainsString('duplicate approval step', $second->json('next_question_text'));
        $this->assertStringContainsString('How did you check whether your approach made a difference?', $second->json('next_question_text'));
        $this->assertNotSame($first->json('next_question_id'), $second->json('next_question_id'));
        $this->assertSame(3, Question::where('interview_session_id', $session->id)->count());
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
