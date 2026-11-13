<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewAnswerProviderReview;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\PreparedAnswerReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProviderOnlyAnswerReviewDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const ANSWER = 'I mapped the support handoff issue, documented missed approvals, coordinated the checklist update, and confirmed the final handoff result with my supervisor.';
    private const FEEDBACK = 'You said "'.self::ANSWER.'" This directly answers the support handoff question with your action and final check. It helps the interviewer judge your handoff approach. Next, add the true effect on the team or customer.';
    private const POSSIBLE = 'I mapped the support handoff issue. I documented missed approvals and coordinated the checklist update. I confirmed the final handoff result with my supervisor.';

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
    }

    public function test_each_question_displays_only_its_verified_provider_review_on_desktop_and_mobile(): void
    {
        [$user, $session, $verified] = $this->fixture();
        $this->verifyPreparedProviderNarrative($verified);
        $local = $this->answer($session, 'local');
        $local->update([
            'ai_feedback' => 'This locally generated review must not be used as provider feedback.',
            'better_sample_answer' => 'This local possible answer must not be displayed.',
        ]);

        foreach (['desktop', 'mobile'] as $layout) {
            $cards = $this->reviewCards($user, $session, $layout);
            $this->assertCount(2, $cards);
            $this->assertStringContainsString(self::FEEDBACK, $cards[0]);
            $this->assertStringContainsString(self::POSSIBLE, $cards[0]);
            $this->assertStringContainsString(self::ANSWER, $cards[1]);
            $this->assertStringContainsString('Provider feedback will appear after the AI review passes validation.', $cards[1]);
            $this->assertStringNotContainsString('Possible Answer', $cards[1]);
            $this->assertStringNotContainsString('locally generated review', $cards[1]);
            $this->assertStringNotContainsString('local possible answer', $cards[1]);
        }
        Http::assertNothingSent();
    }

    public function test_answer_review_uses_tabs_for_feedback_answer_and_possible_answer(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $this->verifyPreparedProviderNarrative($answer);

        foreach (['desktop', 'mobile'] as $layout) {
            $html = $this->reviewHtml($user, $session, $layout);

            $this->assertSame(1, substr_count($html, 'data-answer-review-tabs>'));
            $this->assertSame(1, substr_count($html, 'data-review-tab="feedback"'));
            $this->assertSame(1, substr_count($html, 'data-review-tab="answer"'));
            $this->assertSame(1, substr_count($html, 'data-review-tab="possible"'));
            $this->assertStringContainsString('aria-selected="true"', $html);
            $this->assertStringContainsString('data-review-panel="feedback"', $html);
            $this->assertStringContainsString('data-review-panel="answer"', $html);
            $this->assertStringContainsString('data-review-panel="possible"', $html);
        }
    }

    public function test_provider_name_without_verified_narrative_does_not_authorize_legacy_text(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $answer->update(['coaching_feedback' => ['content_alignment' => [
            'answer_review_source' => 'ai_provider',
            'answer_review_provider' => 'openai',
            'possible_answer_source' => 'ai_provider',
            'possible_answer_provider' => 'openai',
        ]]]);

        foreach (['desktop', 'mobile'] as $layout) {
            $card = $this->reviewCards($user, $session, $layout)[0];
            $this->assertStringNotContainsString(self::FEEDBACK, $card);
            $this->assertStringNotContainsString(self::POSSIBLE, $card);
            $this->assertStringContainsString('Provider feedback will appear after the AI review passes validation.', $card);
        }
    }

    public function test_edited_input_and_modified_provider_text_cannot_bypass_display_checks(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $row = $this->verifyPreparedProviderNarrative($answer);
        $answer->update(['ai_feedback' => 'A changed note must not inherit an earlier provider verification.']);
        $card = $this->reviewCards($user, $session, 'desktop')[0];
        $this->assertStringNotContainsString('A changed note', $card);
        $this->assertStringContainsString(self::FEEDBACK, $card);
        $this->assertStringContainsString(self::POSSIBLE, $card);

        $originalPayload = $row->payload;
        $changedPayload = $originalPayload;
        $changedPayload['ai_feedback'] = 'You achieved a 50% gain in the support handoff.';
        $row->update(['payload' => $changedPayload]);
        $card = $this->reviewCards($user, $session, 'desktop')[0];
        $this->assertStringNotContainsString('50% gain', $card);
        $this->assertStringContainsString('Provider feedback will appear after the AI review passes validation.', $card);
        $row->update(['payload' => $originalPayload]);

        $answer->update(['answer_text' => 'I only observed the handoff and did not perform the checklist update.']);
        foreach (['desktop', 'mobile'] as $layout) {
            $card = $this->reviewCards($user, $session, $layout)[0];
            $this->assertStringContainsString('I only observed the handoff', $card);
            $this->assertStringNotContainsString(self::POSSIBLE, $card);
            $this->assertStringContainsString('Provider feedback will appear after the AI review passes validation.', $card);
        }
    }

    public function test_unanswered_and_ended_sessions_do_not_invent_a_possible_answer(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $answer->update(['is_skipped' => true, 'answer_text' => '']);
        $this->verifyPreparedProviderNarrative(
            $answer,
            'No answer was provided for the support handoff question. Give the handoff issue and your action in a future answer.',
            ''
        );
        $card = $this->reviewCards($user, $session, 'mobile')[0];
        $this->assertStringContainsString('No answer was provided for the support handoff question.', $card);
        $this->assertStringContainsString('No possible answer is generated for an unanswered question.', $card);
        $this->assertStringNotContainsString(self::POSSIBLE, $card);

        $session->update(['status' => 'ended']);
        $card = $this->reviewCards($user, $session, 'desktop')[0];
        $this->assertStringContainsString('it was not assessed because the interview ended early', $card);
        $this->assertStringNotContainsString('Possible Answer', $card);
    }

    public function test_reviewed_short_answer_does_not_show_a_permanently_pending_possible_answer(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $feedback = 'The saved answer "Index" is too short to assess the database query approach. Add the exact check and what it showed.';
        $answer->question->update([
            'question_text' => 'How did you improve a slow database query?',
            'expected_guide' => 'Explain the query issue, the checks you performed, and the result.',
        ]);
        $answer->update([
            'answer_text' => 'Index.',
            'ai_feedback' => $feedback,
            'better_sample_answer' => '',
        ]);
        $this->verifyPreparedProviderNarrative($answer, $feedback, '');

        foreach (['desktop', 'mobile'] as $layout) {
            $card = $this->reviewCards($user, $session, $layout)[0];
            $this->assertStringContainsString($feedback, $card);
            $this->assertStringContainsString('There is not enough answer detail to create a grounded possible answer.', $card);
            $this->assertStringNotContainsString('not available for this question yet', $card);
            $this->assertStringNotContainsString('AI feedback is not ready', $card);
        }
        Http::assertNothingSent();
    }

    private function fixture(): array
    {
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Behavioral']);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'completed',
            'difficulty' => 'medium',
            'target_position' => 'Support Specialist',
        ]);

        return [$user, $session, $this->answer($session, 'openai')];
    }

    private function answer(InterviewSession $session, string $provider): InterviewAnswer
    {
        $question = Question::create([
            'category_id' => $session->category_id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe how you improved a support handoff.',
            'type' => 'Behavioral',
            'difficulty' => 'medium',
            'expected_guide' => 'Explain the handoff issue, your action, and the final result.',
        ]);

        return InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => self::ANSWER,
            'ai_feedback' => self::FEEDBACK,
            'better_sample_answer' => self::POSSIBLE,
            'ai_provider' => $provider,
            'response_mode' => 'text',
        ])->load('question');
    }

    private function verifyPreparedProviderNarrative(
        InterviewAnswer $answer,
        ?string $feedback = null,
        ?string $possibleAnswer = null
    ): InterviewAnswerProviderReview
    {
        $prepared = app(PreparedAnswerReviewService::class);
        $feedback ??= self::FEEDBACK;
        $possibleAnswer ??= self::POSSIBLE;

        return InterviewAnswerProviderReview::create([
            'interview_session_id' => $answer->interview_session_id,
            'interview_answer_id' => $answer->id,
            'status' => 'verified',
            'input_fingerprint' => $prepared->inputFingerprint($answer->interviewSession, $answer),
            'provider_key' => 'openai',
            'payload' => [
                'id' => $answer->id,
                'evaluation_source' => 'ai_evidence_validated',
                'answer_review_source' => 'ai_provider',
                'provider_narratives_verified' => true,
                'feedback_quality' => ['status' => 'verified', 'completeness_percent' => 100],
                'ai_feedback' => $feedback,
                'better_sample_answer' => $possibleAnswer,
                'better_sample_answer_source' => 'ai_provider',
                'better_sample_answer_provider' => 'openai',
                'insufficient_data' => trim((string) $answer->answer_text) === 'Index.',
            ],
        ]);
    }

    private function reviewCards(User $user, InterviewSession $session, string $layout): array
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$this->reviewHtml($user, $session, $layout));
        $nodes = (new \DOMXPath($document))->query('//div[contains(concat(" ", normalize-space(@class), " "), " review-answer-simple ")]');

        return array_map(static fn (\DOMNode $node): string => $node->textContent, iterator_to_array($nodes));
    }

    private function reviewHtml(User $user, InterviewSession $session, string $layout): string
    {
        $response = $this->actingAs($user)->get(route('user.review', $session).'?sr_layout='.$layout);
        $response->assertOk()->assertViewIs($layout.'.user.review');

        return $response->getContent();
    }
}
