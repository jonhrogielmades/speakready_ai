<?php

namespace Tests\Feature;

use App\Jobs\GenerateAnswerProviderFeedback;
use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewAnswerProviderReview;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\AIService;
use App\Services\DetailedFeedbackReviewService;
use App\Services\PreparedAnswerReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PreparedAnswerProviderReviewTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => '', 'GROQ_API_KEY' => '', 'COHERE_API_KEY' => '',
        ] as $key => $value) {
            $this->originalEnv[$key] = getenv($key);
            putenv("{$key}={$value}");
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $_SERVER[$key] = $value;
            }
        }
        parent::tearDown();
    }

    public function test_answer_and_chat_reply_queue_preparation_after_saving_without_waiting_for_review_api(): void
    {
        [$user, $session, $question] = $this->fixture();
        $this->actingAs($user)->withSession(['active_interview_id' => $session->id])
            ->postJson(route('interview.answer'), [
                'session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => $this->answerText(),
            ])->assertOk()->assertJsonPath('success', true);

        $answer = InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail();
        Queue::assertPushed(GenerateAnswerProviderFeedback::class, fn ($job) =>
            $job->sessionId === $session->id && $job->answerId === $answer->id
            && $job->queue === 'review-answers');
        Http::assertNothingSent();

        [$otherUser, $otherSession, $otherQuestion] = $this->fixture();
        $this->actingAs($otherUser)->withSession(['active_interview_id' => $otherSession->id])
            ->postJson(route('interview.chatReply'), [
                'session_id' => $otherSession->id,
                'question_id' => $otherQuestion->id,
                'answer_text' => $this->answerText(),
            ])->assertOk()->assertJsonPath('interview_completed', true);
        $otherAnswer = InterviewAnswer::where('interview_session_id', $otherSession->id)->firstOrFail();
        Queue::assertPushed(GenerateAnswerProviderFeedback::class, fn ($job) =>
            $job->sessionId === $otherSession->id && $job->answerId === $otherAnswer->id);
        Http::assertNothingSent();
    }

    public function test_worker_saves_a_current_api_provider_item_and_reuses_it_without_another_request(): void
    {
        [, $session, , $answer] = $this->savedAnswerFixture();
        Http::fake(Http::response($this->openAiResponse($this->answerPayload($answer))));
        $job = new GenerateAnswerProviderFeedback($session->id, $answer->id);
        $this->app->call([$job, 'handle']);

        $row = InterviewAnswerProviderReview::where('interview_answer_id', $answer->id)->firstOrFail();
        $this->assertSame('verified', $row->status);
        $this->assertSame('openai', $row->provider_key);
        $this->assertSame('ai_evidence_validated', $row->payload['evaluation_source']);
        $prepared = app(PreparedAnswerReviewService::class)->preparedReviewsForSession($session);
        $this->assertNotNull($prepared);
        $this->assertSame('openai', $prepared[0]['_prepared_provider_key']);
        $this->assertSame('openai', $prepared[0]['better_sample_answer_provider']);
        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $prompt = (string) data_get($request->data(), 'messages.1.content', '');
            return str_contains($prompt, 'UNTRUSTED SAVED ANSWER JSON')
                && ! str_contains($prompt, 'UNTRUSTED TRANSCRIPT DATA JSON')
                && data_get($request->data(), 'response_format.json_schema.name') === 'interview_answer_feedback';
        });

        $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);
        Http::assertSentCount(1);
        $this->assertSame(1, InterviewAnswerProviderReview::count());
    }

    public function test_changed_answer_cannot_reuse_a_stale_provider_item(): void
    {
        [, $session, , $answer] = $this->savedAnswerFixture();
        Http::fake(Http::response($this->openAiResponse($this->answerPayload($answer))));
        $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);

        $answer->update(['answer_text' => 'I only watched my teammate inspect the query plan.']);
        $prepared = app(PreparedAnswerReviewService::class);
        $this->assertNull($prepared->preparedReviewsForSession($session));
        $this->assertSame([$answer->id], $prepared->missingAnswerIdsForSession($session));
    }

    public function test_provider_rewrites_only_rejected_visible_fields_before_item_is_saved(): void
    {
        [, $session, , $answer] = $this->savedAnswerFixture();
        $valid = $this->answerPayload($answer);
        $invalid = $valid;
        $invalid['per_question_feedback'][0]['ai_feedback'] .= ' You resolved the database query.';
        $invalid['per_question_feedback'][0]['follow_up_question'] = 'How did you resolve the database query?';
        $invalid['per_question_feedback'][0]['coaching']['impact'] = 'You resolved the database query.';
        Http::fakeSequence()
            ->push($this->openAiResponse($invalid))
            ->push($this->openAiResponse([
                'ai_feedback' => $valid['per_question_feedback'][0]['ai_feedback'],
                'follow_up_question' => $valid['per_question_feedback'][0]['follow_up_question'],
                'coaching' => $valid['per_question_feedback'][0]['coaching'],
            ]));

        $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);

        $row = InterviewAnswerProviderReview::where('interview_answer_id', $answer->id)->firstOrFail();
        $this->assertSame('verified', $row->status);
        $this->assertSame($valid['per_question_feedback'][0]['ai_feedback'], $row->payload['ai_feedback']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool =>
            data_get($request->data(), 'response_format.json_schema.name') === 'interview_answer_prose_correction');
    }

    public function test_invalid_optional_provider_prose_is_suppressed_without_local_substitution(): void
    {
        [, $session, , $answer] = $this->savedAnswerFixture();
        $valid = $this->answerPayload($answer);
        $invalid = $valid;
        $invalid['per_question_feedback'][0]['follow_up_question'] = 'How did you resolve the database query?';
        $invalid['per_question_feedback'][0]['coaching']['impact'] = 'You resolved the database query.';
        Http::fakeSequence()
            ->push($this->openAiResponse($invalid))
            ->push($this->openAiResponse([
                'follow_up_question' => $invalid['per_question_feedback'][0]['follow_up_question'],
                'coaching' => $invalid['per_question_feedback'][0]['coaching'],
            ]));

        try {
            $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);
        } catch (\App\Exceptions\AiFeedbackProviderFailureException $error) {
            $this->fail(json_encode($error->validationErrors()));
        }

        $row = InterviewAnswerProviderReview::where('interview_answer_id', $answer->id)->firstOrFail();
        $this->assertSame('verified', $row->status);
        $this->assertSame('openai', $row->provider_key);
        $this->assertSame('', $row->payload['follow_up_question']);
        $this->assertSame([], $row->payload['provider_coaching']);
        $this->assertSame(['follow_up_question', 'provider_coaching'], $row->payload['provider_optional_narratives_suppressed']);
        $this->assertSame($valid['per_question_feedback'][0]['ai_feedback'], $row->payload['ai_feedback']);
        $this->assertSame($valid['per_question_feedback'][0]['better_sample_answer'], $row->payload['better_sample_answer']);
        $this->assertSame('verified', $row->payload['feedback_quality']['status']);
    }

    public function test_session_summary_provider_corrects_only_rejected_text(): void
    {
        [, $session, , $answer] = $this->savedAnswerFixture();
        $evidence = 'I inspected the query plan, compared row estimates, and verified index usage';
        $validSummary = [
            'overall_summary' => 'The database query answer gave specific query plan and index usage steps. The next focus is explaining the final result of those checks.',
            'strengths' => 'The answer names concrete steps such as "'.$evidence.'", which helps an interviewer understand the database query approach.',
            'weaknesses' => 'The database query answer does not state what the row estimate or index checks showed.',
            'improvement_suggestions' => 'Keep the query plan and index details, then add the true finding from those checks.',
        ];
        $invalidSummary = $validSummary;
        $invalidSummary['strengths'] = 'You resolved the database query.';
        Http::fakeSequence()
            ->push($this->openAiResponse($this->answerPayload($answer)))
            ->push($this->openAiResponse(['session_feedback' => $invalidSummary]))
            ->push($this->openAiResponse(['strengths' => $validSummary['strengths']]));
        $prepared = app(PreparedAnswerReviewService::class);
        $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);

        $review = AIService::generatePreparedReviewSummary(
            $prepared->sessionData($session),
            [$prepared->answerData($answer)],
            $prepared->preparedReviewsForSession($session),
            30
        );

        $this->assertSame('openai', $review['_provider_key']);
        $this->assertTrue($review['_strict_validation_passed']);
        $this->assertSame($validSummary['strengths'], $review['session_feedback']['strengths']);
        Http::assertSentCount(3);
        Http::assertSent(fn ($request): bool =>
            data_get($request->data(), 'response_format.json_schema.name') === 'interview_session_feedback_correction');
    }

    public function test_last_prepared_answer_resumes_a_completed_unverified_review(): void
    {
        [$user, $session, , $answer] = $this->savedAnswerFixture();
        $session->forceFill(['status' => 'completed', 'feedback_status' => 'pending'])->save();
        Http::fake(Http::response($this->openAiResponse($this->answerPayload($answer))));

        $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);

        Queue::assertPushed(GenerateInterviewFeedback::class, fn ($job) =>
            $job->sessionId === $session->id && $job->userId === $user->id);
    }

    public function test_completed_customer_interview_persists_a_provider_verified_detailed_review_for_the_saved_answer(): void
    {
        [$user, $session, $question] = $this->fixture();
        $session->forceFill([
            'status' => 'completed',
            'feedback_status' => 'pending',
            'target_position' => 'Customer Service Representative',
        ])->save();
        $question->forceFill([
            'question_text' => 'Tell me about a time you handled a difficult customer.',
            'type' => 'Behavioral',
        ])->save();
        $savedText = 'I listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.';
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => $savedText,
            'response_mode' => 'text',
        ]);
        $evidence = 'I listened to an upset customer, apologized for the delivery delay, checked the order status';
        $item = [
            'id' => $answer->id,
            'score' => 82, 'clarity_score' => 82, 'relevance_score' => 86,
            'grammar_score' => 82, 'professionalism_score' => 82,
            'star_applicable' => true, 'star_method_score' => 75,
            'evidence_quotes' => [$evidence],
            'question_focus' => $question->question_text,
            'answer_alignment' => 'directly_addressed', 'missing_criteria' => [],
            'ai_feedback' => 'The answer addresses handling a difficult customer through the exact action "'.$evidence.'". It also states that the customer received the item the next day and thanked us for the update. Add what the order-status check showed if you know it.',
            'better_sample_answer' => 'I listened to an upset customer and apologized for the delivery delay. I checked the order status and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.',
            'follow_up_question' => 'What did the order-status check show?',
            'coaching' => [
                'keep' => 'Keep the detail "'.$evidence.'" in the customer example.',
                'improve' => 'Add what the order-status check showed if you know it.',
                'impact' => 'The answer does not say what the order-status check showed, so the interviewer cannot see how that check informed the next step.',
                'next_try' => 'Retell the same customer example and add the true order-status detail if you know it.',
                'next_attempt_steps' => [
                    'State that you listened to an upset customer and apologized for the delivery delay.',
                    'Explain what the order-status check showed if you know it.',
                ],
                'success_check' => 'A stronger retry keeps the saved actions and adds only a true detail about the order-status check.',
            ],
        ];
        $summary = [
            'overall_summary' => 'The saved answer describes listening to an upset customer, apologizing for the delivery delay, checking the order status, and coordinating with the delivery team. It states that the customer received the item the next day and thanked us for the update.',
            'strengths' => 'The answer gives a specific sequence, including "'.$evidence.'". These actions directly address the difficult-customer question.',
            'weaknesses' => 'The answer does not say what the order-status check showed or what coordination with the delivery team involved.',
            'improvement_suggestions' => 'Keep the saved sequence of listening, apologizing, checking, and coordinating. If known, add what the order-status check showed.',
        ];
        Http::fakeSequence()
            ->push($this->openAiResponse(['per_question_feedback' => [$item]]))
            ->push($this->openAiResponse(['session_feedback' => $summary]));

        $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);
        $prepared = app(PreparedAnswerReviewService::class);
        $row = InterviewAnswerProviderReview::where('interview_answer_id', $answer->id)->firstOrFail();
        $this->assertSame('verified', $row->status);
        $this->assertSame($prepared->inputFingerprint($session, $answer), $row->input_fingerprint);

        $this->app->call([new GenerateInterviewFeedback($session->id, $user->id), 'handle']);

        $savedSession = $session->fresh(['answers.question', 'feedback', 'score']);
        $this->assertSame('verified', $savedSession->feedback_status);
        $this->assertNotNull($savedSession->feedback);
        $this->assertNotNull($savedSession->score);
        $this->assertSame('openai', $answer->fresh()->ai_provider);
        $this->assertFalse(app(DetailedFeedbackReviewService::class)
            ->shouldRefreshWithProvider($savedSession));
        Http::assertSentCount(2);

        $answer->forceFill(['answer_text' => 'I only listened to the customer.'])->save();
        $this->assertTrue(app(DetailedFeedbackReviewService::class)
            ->shouldRefreshWithProvider($session->fresh(['answers.question', 'feedback', 'score'])));
    }

    public function test_exhausted_provider_attempt_records_a_terminal_failure_for_recovery(): void
    {
        [, $session, , $answer] = $this->savedAnswerFixture();
        Http::fake(Http::response(['error' => ['message' => 'Rate limited']], 429));
        $job = new GenerateAnswerProviderFeedback($session->id, $answer->id);
        try {
            $this->app->call([$job, 'handle']);
            $this->fail('The provider failure should make the queue retry.');
        } catch (\Throwable $error) {
            $row = InterviewAnswerProviderReview::where('interview_answer_id', $answer->id)->firstOrFail();
            $this->assertSame('pending', $row->status);
            $job->failed($error);
        }

        $row->refresh();
        $this->assertSame('failed', $row->status);
        $this->assertFalse(app(PreparedAnswerReviewService::class)->hasPendingPreparationsForSession($session));
    }

    public function test_terminally_failed_current_preparation_does_not_hold_final_review_pending(): void
    {
        [, $session, , $answer] = $this->savedAnswerFixture();
        $prepared = app(PreparedAnswerReviewService::class);
        InterviewAnswerProviderReview::create([
            'interview_session_id' => $session->id,
            'interview_answer_id' => $answer->id,
            'status' => 'failed',
            'input_fingerprint' => $prepared->inputFingerprint($session, $answer),
            'failure_code' => 'provider_timeout',
        ]);

        $this->assertFalse($prepared->hasPendingPreparationsForSession($session));
        $this->assertSame(1, $prepared->progressForSession($session)['failed']);
    }

    public function test_spoken_draft_can_split_saved_customer_clauses_without_adding_a_claim(): void
    {
        $saved = 'I listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.';
        $draft = 'I listened to an upset customer and apologized for the delivery delay. I then checked the order status and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.';
        $method = new \ReflectionMethod(\App\Services\AIService::class, 'providerBetterSampleAnswerIsValid');
        $this->assertTrue($method->invoke(null, $draft, [
            'answer' => $saved,
            'question' => 'How did you handle a difficult customer?',
        ]));
    }

    public function test_provider_feedback_can_describe_an_approach_without_claiming_a_result(): void
    {
        $saved = 'I listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.';
        $texts = [
            'Your answer directly addressed the question by detailing how you handled a difficult customer. You mentioned, "I listened to an upset customer," which shows your approach to resolving the issue. To improve, consider adding more about the customer\'s reaction after receiving the item.',
            'Your answer directly addressed the question by explaining how you handled a difficult customer. You mentioned, "I listened to an upset customer," which illustrates your approach to the situation. To enhance your response, consider detailing the customer\'s feelings after receiving the item. This would provide a clearer picture of the outcome.',
        ];
        $answer = ['answer' => $saved, 'question' => 'How did you handle a difficult customer?'];
        $method = new \ReflectionMethod(\App\Services\AIService::class, 'providerVisibleTextHasUnsupportedSpecificClaims');
        $results = array_map(fn (string $text): bool => $method->invoke(null, $text, $answer), $texts);
        $this->assertSame([false, false], $results);
        $this->assertTrue($method->invoke(null, 'You resolved the customer\'s issue.', $answer));
    }

    public function test_session_summary_does_not_reject_explicit_missing_detail_or_negated_claim(): void
    {
        $saved = 'I listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.';
        $answer = ['answer' => $saved, 'question' => 'How did you handle a difficult customer?'];
        $strengths = 'You describe a clear sequence: listening to the upset customer, apologizing for the delivery delay, checking the order status, and coordinating with the delivery team. These specific actions explain how you responded rather than merely claiming that you handled the situation well.';
        $weaknesses = 'The answer does not explain what the order-status check showed or what coordination with the delivery team involved. It also does not specify the customer\'s reaction after receiving the item. These are missing details, not evidence of weak handling; the thank-you was explicitly for the update, not proof of customer satisfaction.';
        $method = new \ReflectionMethod(\App\Services\AIService::class, 'sessionFeedbackHasUnsupportedSpecificClaims');
        $this->assertFalse($method->invoke(null, $strengths, [$answer]));
        $this->assertFalse($method->invoke(null, $weaknesses, [$answer]));
        $this->assertTrue($method->invoke(null, 'The customer was satisfied with the result.', [$answer]));
    }

    public function test_session_summary_accepts_source_faithful_meta_description(): void
    {
        $answer = [
            'answer' => 'I listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.',
            'question' => 'How did you handle a difficult customer?',
        ];
        $texts = [
            'Your one saved answer directly addressed handling a difficult customer through a delivery-delay example. You described listening, apologizing, checking the order status, and coordinating with the delivery team. The stated outcome was that the customer "received the item the next day and thanked us for the update." This single example does not establish a repeated pattern across customer interactions.',
            'You connected your response to the customer\'s specific concern: you "apologized for the delivery delay" and followed that with an order-status check and coordination with the delivery team. These concrete actions make the answer relevant to customer support rather than simply claiming that you handled the situation well.',
            'The candidate described listening to an upset customer, apologizing for a delivery delay, checking the order status, and coordinating with the delivery team. The candidate noted that the customer received the item the next day and thanked us for the update.',
            'Candidate described handling a difficult customer by listening, apologizing for the delivery delay, checking the order status, coordinating with the delivery team, and the customer received the item the next day and thanked the team for the update.',
        ];
        $method = new \ReflectionMethod(AIService::class, 'sessionFeedbackHasUnsupportedSpecificClaims');
        $results = array_map(fn (string $text): bool => $method->invoke(null, $text, [$answer]), $texts);
        $this->assertSame([false, false, false, false], $results);
        $this->assertTrue($method->invoke(null, 'The candidate resolved the customer issue.', [$answer]));
        $this->assertTrue($method->invoke(null, 'Candidate ensured the customer received the item.', [$answer]));
    }

    public function test_session_summary_accepts_question_framing_and_saved_actions(): void
    {
        $answer = [
            'answer' => 'I listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.',
            'question' => 'How did you handle a difficult customer?',
            'question_type' => 'Behavioral',
        ];
        $texts = [
            'You described concrete actions: listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with the delivery team. These details explain what you did in response to the customer\'s concern rather than merely claiming good customer service.',
            'The candidate provided a single behavioral response describing how they handled a difficult customer, detailing listening, apologizing, checking order status, coordinating with delivery, and noting that the customer received the item the next day and thanked us for the update.',
        ];
        $method = new \ReflectionMethod(AIService::class, 'sessionFeedbackHasUnsupportedSpecificClaims');
        $results = array_map(fn ($text) => $method->invoke(null, $text, [$answer]), $texts);
        $this->assertSame([false, false], $results);
        $this->assertTrue($method->invoke(null, 'The candidate handled the customer situation perfectly.', [$answer]));
    }

    private function fixture(): array
    {
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Technical']);
        $session = InterviewSession::create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'difficulty' => 'medium', 'target_position' => 'Database Developer',
            'num_questions' => 1, 'response_mode' => 'text', 'status' => 'in_progress',
        ]);
        $question = Question::create([
            'category_id' => $category->id, 'interview_session_id' => $session->id,
            'question_text' => 'How would you diagnose a slow database query?',
            'type' => 'Technical', 'difficulty' => 'medium', 'status' => 'active',
        ]);

        return [$user, $session, $question];
    }

    private function savedAnswerFixture(): array
    {
        [$user, $session, $question] = $this->fixture();
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id, 'question_id' => $question->id,
            'answer_text' => $this->answerText(), 'response_mode' => 'text',
        ]);

        return [$user, $session, $question, $answer];
    }

    private function answerText(): string
    {
        return 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.';
    }

    private function answerPayload(InterviewAnswer $answer): array
    {
        $question = $answer->question->question_text;
        $evidence = 'I inspected the query plan, compared row estimates, and verified index usage';

        return ['per_question_feedback' => [[
            'id' => $answer->id, 'score' => 82, 'clarity_score' => 80,
            'relevance_score' => 86, 'grammar_score' => 82, 'professionalism_score' => 80,
            'star_applicable' => false, 'star_method_score' => 0,
            'evidence_quotes' => [$evidence], 'question_focus' => $question,
            'answer_alignment' => 'directly_addressed', 'missing_criteria' => [],
            'ai_feedback' => 'For "'.$question.'", you stated "'.$evidence.'", which directly supports a relevant database query approach. This helps the interviewer assess the database query diagnosis.',
            'better_sample_answer' => $evidence.'. I would keep those same diagnostic steps and connect them to the role requirement. I would add the true final result after confirming it.',
            'follow_up_question' => 'What final result or detail from this answer would make it stronger?',
            'coaching' => [
                'keep' => 'Keep the detail "'.$evidence.'" for "'.$question.'".',
                'improve' => 'Add the final database query result or check for "'.$question.'".',
                'impact' => 'The missing database query result limits how well the interviewer can judge whether the approach worked.',
                'next_try' => 'Answer "'.$question.'" by linking the database query steps to the verified result.',
                'next_attempt_steps' => [
                    'Start with the database query symptom you would check first.',
                    'Use "'.$evidence.'" as support.',
                ],
                'success_check' => 'The retry links the database query diagnosis to a verified result.',
            ],
        ]]];
    }

    private function openAiResponse(array $payload): array
    {
        return ['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode($payload)],
        ]]];
    }
}
