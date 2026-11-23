<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\InterviewSessionLocalFeedbackPreview;
use App\Models\InterviewSessionProviderPreview;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use App\Services\ProviderSessionPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LocalFeedbackOverallReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_pending_and_failed_reviews_show_all_local_summary_sections_without_stale_provider_prose(): void
    {
        [$user, $session] = $this->fixture();
        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Stale provider strength must stay hidden.',
            'weaknesses' => 'Stale provider weakness must stay hidden.',
            'improvement_suggestions' => 'Stale provider suggestion must stay hidden.',
            'coaching_summary' => ['overall_summary' => 'Stale provider summary must stay hidden.'],
        ]);

        foreach (['pending', 'failed'] as $status) {
            $session->update(['feedback_status' => $status]);
            foreach (['desktop', 'mobile'] as $device) {
                $response = $this->actingAs($user)
                    ->withHeader('User-Agent', $device === 'mobile'
                        ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148'
                        : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36')
                    ->get(route('user.review', $session));

                $response->assertOk()
                    ->assertSee('Overall Feedback Preview')
                    ->assertSee('Overall Answer Summary')
                    ->assertSee('Strengths')
                    ->assertSee('Weaknesses')
                    ->assertSee('What to Practice Next')
                    ->assertSee('Total Score')
                    ->assertSee('Local estimate')
                    ->assertSee('query plan')
                    ->assertDontSee('Stale provider summary must stay hidden.')
                    ->assertDontSee('Stale provider strength must stay hidden.')
                    ->assertDontSee('AI Feedback Review');
            }
        }

        Http::assertNothingSent();
    }

    public function test_local_summary_uses_current_saved_answer_after_an_edit(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $this->actingAs($user)->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('inspected the query plan');

        $answer->update([
            'answer_text' => 'I compared the index statistics and checked row estimates before changing the database query.',
        ]);

        $this->actingAs($user)->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Overall Feedback Preview')
            ->assertSee('index statistics')
            ->assertDontSee('inspected the query plan');

        Http::assertNothingSent();
    }

    public function test_local_overall_and_answer_feedback_are_reused_from_database_and_invalidated_by_saved_input_changes(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $this->actingAs($user)->get(route('user.review', $session))
            ->assertOk()->assertSee('Overall Feedback Preview');

        $stored = InterviewSessionLocalFeedbackPreview::where('interview_session_id', $session->id)->firstOrFail();
        $this->assertNotEmpty(data_get($stored->payload, 'session_feedback.overall_summary'));
        $this->assertNotEmpty(data_get($stored->payload, "answer_feedback.{$answer->id}.ai_feedback"));
        $this->assertIsInt(data_get($stored->payload, 'overall_score'));

        $saved = $stored->payload;
        $saved['overall_score'] = 99;
        $saved['session_feedback']['overall_summary'] = 'Saved local summary for the query plan and index usage answer.';
        $saved['answer_feedback'][(string) $answer->id]['ai_feedback'] = 'Saved local feedback for the query plan and index usage answer.';
        $stored->update(['payload' => $saved]);
        Cache::flush();

        $this->actingAs($user)->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('99%')
            ->assertSee('Saved local summary for the query plan and index usage answer.')
            ->assertSee('Saved local feedback for the query plan and index usage answer.');

        $answer->update(['answer_text' => 'I compared index statistics and row estimates before changing the query.']);
        $this->actingAs($user)->get(route('user.review', $session))
            ->assertOk()
            ->assertDontSee('99%')
            ->assertDontSee('Saved local summary for the query plan and index usage answer.')
            ->assertDontSee('Saved local feedback for the query plan and index usage answer.')
            ->assertSee('index statistics');

        $this->assertNotSame($stored->input_fingerprint, $stored->fresh()->input_fingerprint);
        $afterAnswerEditFingerprint = $stored->fresh()->input_fingerprint;
        $answer->question->update(['question_text' => 'Explain how you checked index statistics for a slow query.']);
        $this->actingAs($user)->get(route('user.review', $session))
            ->assertOk()->assertSee('Explain how you checked index statistics');
        $this->assertNotSame($afterAnswerEditFingerprint, $stored->fresh()->input_fingerprint);
        Http::assertNothingSent();
    }

    public function test_current_provider_summary_replaces_local_preview_and_stale_provider_summary_is_hidden(): void
    {
        [$user, $session, $answer] = $this->fixture();
        InterviewSessionProviderPreview::create([
            'interview_session_id' => $session->id,
            'provider_key' => 'openai',
            'input_fingerprint' => app(ProviderSessionPreviewService::class)->inputFingerprint($session),
            'session_feedback' => [
                'overall_summary' => 'Across the database query answer, the response used specific database query details. The next focus is to add the final result.',
                'strengths' => 'The AI review used saved answer details such as "I inspected the query plan, compared row estimates, and verified index usage" to identify what worked.',
                'weaknesses' => 'The database query answer could add the final result from the same work.',
                'improvement_suggestions' => 'Keep the database query steps and add the outcome only if it is true.',
            ],
        ]);

        foreach (['pending', 'failed'] as $status) {
            $session->update(['feedback_status' => $status]);
            $this->actingAs($user)->get(route('user.review', $session))
                ->assertOk()
                ->assertSee('AI provider session review')
                ->assertSee('AI Feedback Review')
                ->assertSee('Total Score')
                ->assertSee('Local estimate')
                ->assertSee('Across the database query answer, the response used specific database query details.')
                ->assertDontSee('Overall Feedback Preview');
        }

        $answer->update([
            'answer_text' => 'I compared the index statistics and checked row estimates before changing the database query.',
        ]);

        $this->actingAs($user)->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Overall Feedback Preview')
            ->assertSee('index statistics')
            ->assertDontSee('Across the database query answer, the response used specific database query details.');

        Http::assertNothingSent();
    }

    public function test_feedback_center_uses_current_local_total_instead_of_a_stale_provider_score(): void
    {
        [$user, $session] = $this->fixture();
        Score::create([
            'interview_session_id' => $session->id,
            'overall_readiness_score' => 99,
            'readiness_band' => 'Excellent',
        ]);
        $session->update(['feedback_status' => 'failed']);

        foreach (['desktop', 'mobile'] as $device) {
            $response = $this->actingAs($user)
                ->withHeader('User-Agent', $device === 'mobile'
                    ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148'
                    : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36')
                ->get(route('user.feedback'));

            $localScore = data_get(
                InterviewSessionLocalFeedbackPreview::where('interview_session_id', $session->id)->firstOrFail()->payload,
                'overall_score'
            );
            $this->assertIsInt($localScore);
            $response->assertOk()
                ->assertSee('Local estimate')
                ->assertViewHas('feedbackSummary', fn ($summary): bool => $summary
                    && $summary->overall === $localScore
                    && $summary->score_source === 'local');
        }

        Http::assertNothingSent();
    }

    /** @return array{User, InterviewSession, InterviewAnswer} */
    private function fixture(): array
    {
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Technical']);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Database Developer',
            'num_questions' => 1,
            'response_mode' => 'text',
            'status' => 'completed',
            'feedback_status' => 'pending',
        ]);
        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'How would you diagnose a slow database query?',
            'type' => 'Technical',
            'difficulty' => 'medium',
            'status' => 'active',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
            'response_mode' => 'text',
        ]);

        return [$user, $session, $answer];
    }
}
