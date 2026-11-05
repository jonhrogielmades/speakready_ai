<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
