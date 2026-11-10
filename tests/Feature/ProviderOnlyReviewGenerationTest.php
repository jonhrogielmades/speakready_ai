<?php

namespace Tests\Feature;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderOnlyReviewGenerationTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini',
            'AI_FEEDBACK_MAX_PROVIDERS' => '2',
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

    public function test_strict_review_preserves_provider_prose_without_local_additions(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];
        $this->assertTrue($review['_provider_narratives_verified']);
        $this->assertTrue($review['review_context']['provider_narratives_verified']);
        $this->assertSame('ai_provider', $item['answer_review_source']);
        $this->assertSame('ai_provider', $item['better_sample_answer_source']);
        foreach (['ai_feedback', 'better_sample_answer', 'follow_up_question'] as $field) {
            $this->assertSame($payload['per_question_feedback'][0][$field], $item[$field]);
        }
        $this->assertSame($payload['per_question_feedback'][0]['coaching'], $item['provider_coaching']);
        foreach ($payload['session_feedback'] as $field => $text) {
            $this->assertSame($text, $review['session_feedback'][$field]);
        }
        Http::assertSentCount(1);
    }

    public function test_invalid_coaching_cannot_be_locally_repaired_in_provider_only_mode(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $payload['per_question_feedback'][0]['coaching']['impact'] = 'You earned a promotion and increased revenue by 50%.';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $this->expectException(AiFeedbackProviderFailureException::class);
        $this->generate($answer);
    }

    public function test_invalid_summary_cannot_be_locally_repaired_in_provider_only_mode(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $payload['session_feedback']['overall_summary'] = 'You earned a promotion and increased revenue by 50%.';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $this->expectException(AiFeedbackProviderFailureException::class);
        $this->generate($answer);
    }

    public function test_strict_review_fails_over_to_another_api_provider(): void
    {
        $answer = $this->answer();
        $valid = $this->payload($answer);
        $invalid = $valid;
        $invalid['per_question_feedback'][0]['coaching']['impact'] = 'You earned a promotion and increased revenue by 50%.';
        Http::fake(function ($request) use ($valid, $invalid) {
            if (str_contains($request->url(), 'api.openai.com')) {
                return Http::response($this->openAiResponse($invalid));
            }

            return Http::response(['candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => json_encode($valid)]]],
            ]]]);
        });
        $review = $this->generate($answer, false);
        $this->assertSame('gemini', $review['_provider_key']);
        $this->assertSame(['openai', 'gemini'], $review['_providers_attempted']);
        $this->assertTrue($review['_provider_narratives_verified']);
        $this->assertFalse($review['_repair_applied']);
        $this->assertSame($valid['per_question_feedback'][0]['ai_feedback'], $review['per_question_feedback'][0]['ai_feedback']);
        Http::assertSentCount(2);
    }

    public function test_short_answer_keeps_the_provider_note_and_can_leave_the_draft_empty(): void
    {
        $answer = array_replace($this->answer(), ['answer' => 'Index.']);
        $payload = $this->payload($answer);
        foreach (['score', 'clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score'] as $field) {
            $payload['per_question_feedback'][0][$field] = 5;
        }
        $payload['per_question_feedback'][0]['answer_alignment'] = 'insufficient_evidence';
        $payload['per_question_feedback'][0]['ai_feedback'] = 'The saved answer "Index" is too short to assess the database query approach. Add the exact check and what it showed.';
        $payload['per_question_feedback'][0]['better_sample_answer'] = '';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];
        $this->assertSame($payload['per_question_feedback'][0]['ai_feedback'], $item['ai_feedback']);
        $this->assertSame('', $item['better_sample_answer']);
        $this->assertSame('none', $item['better_sample_answer_source']);
        $this->assertSame('ai_provider', $item['answer_review_source']);
    }

    private function generate(array $answer, bool $providerOnly = true): array
    {
        return AIService::generateFeedback(['target_position' => 'Database Developer'], [$answer], 'openai', $providerOnly, false, [
            'require_provider_narratives' => true,
            'deadline_seconds' => 20,
            'max_attempts' => 1,
            'http_attempts' => 1,
            'retry_delay_ms' => 0,
        ]);
    }

    public function test_skipped_response_keeps_the_provider_note_without_inventing_a_draft(): void
    {
        $answer = array_replace($this->answer(), ['answer' => '', 'is_skipped' => true]);
        $payload = $this->payload($answer);
        foreach (['score', 'clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score'] as $field) {
            $payload['per_question_feedback'][0][$field] = 0;
        }
        $payload['per_question_feedback'][0]['answer_alignment'] = 'skipped';
        $payload['per_question_feedback'][0]['evidence_quotes'] = [];
        $payload['per_question_feedback'][0]['ai_feedback'] = 'No database query answer was saved. The skipped response cannot support a diagnosis review.';
        $payload['per_question_feedback'][0]['better_sample_answer'] = '';
        $payload['session_feedback']['strengths'] = 'No saved database query answer supports an observed strength.';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];
        $this->assertSame($payload['per_question_feedback'][0]['ai_feedback'], $item['ai_feedback']);
        $this->assertSame('', $item['better_sample_answer']);
        $this->assertSame('none', $item['better_sample_answer_source']);
        $this->assertTrue($item['is_skipped']);
    }

    private function answer(): array
    {
        return [
            'id' => 71,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
        ];
    }

    private function openAiResponse(array $payload): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($payload)]]]];
    }

    private function payload(array $answer): array
    {
        $question = $answer['question'];
        $evidence = preg_split('/\s+(?:before|after)\s+/i', rtrim($answer['answer'], '.'), 2)[0];
        return [
            'per_question_feedback' => [[
                'id' => $answer['id'], 'score' => 82, 'clarity_score' => 80, 'relevance_score' => 86,
                'grammar_score' => 82, 'professionalism_score' => 80, 'star_applicable' => false, 'star_method_score' => 0,
                'evidence_quotes' => [$evidence], 'question_focus' => $question, 'answer_alignment' => 'directly_addressed', 'missing_criteria' => [],
                'ai_feedback' => 'For "'.$question.'", you stated "'.$evidence.'", which directly supports a relevant database query approach.',
                'better_sample_answer' => $evidence.'. I would keep those same diagnostic steps and connect them to the role requirement. I would add the true final result after confirming it.',
                'follow_up_question' => 'What final result or detail from this answer would make it stronger?',
                'coaching' => [
                    'keep' => 'Keep the detail "'.$evidence.'" for "'.$question.'".',
                    'improve' => 'Add the final database query result or check for "'.$question.'".',
                    'impact' => 'The missing database query result limits how well the interviewer can judge whether the approach worked.',
                    'next_try' => 'Answer "'.$question.'" by linking the database query steps to the verified result.',
                    'next_attempt_steps' => ['Start with the database query symptom you would check first.', 'Use "'.$evidence.'" as support.'],
                    'success_check' => 'The retry links the database query diagnosis to a verified result.',
                ],
            ]],
            'session_feedback' => [
                'overall_summary' => 'Across the database query answer, the response used specific database query details. The next focus is to add the final result.',
                'strengths' => 'The AI review used saved answer details such as "'.$evidence.'" to identify what worked.',
                'weaknesses' => 'The database query answer could add the final result from the same work.',
                'improvement_suggestions' => 'Keep the database query steps and add the outcome only if it is true.',
            ],
        ];
    }
}
