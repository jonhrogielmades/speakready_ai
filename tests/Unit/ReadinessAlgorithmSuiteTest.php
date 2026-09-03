<?php

namespace Tests\Unit;

use App\Models\LearningModule;
use App\Models\Score;
use App\Services\ReadinessAlgorithmSuite;
use PHPUnit\Framework\TestCase;

class ReadinessAlgorithmSuiteTest extends TestCase
{
    public function test_it_runs_all_supported_algorithms_when_data_is_available(): void
    {
        $suite = new ReadinessAlgorithmSuite;
        $target = $this->score([
            'id' => 100,
            'interview_session_id' => 500,
            'clarity_score' => 52,
            'relevance_score' => 88,
            'grammar_score' => 86,
            'professionalism_score' => 84,
            'confidence_score' => 80,
            'overall_readiness_score' => 76,
        ]);

        $result = $suite->analyze($target, $this->trainingScores(), [
            $this->module([
                'id' => 1,
                'title' => 'Answer Clarity Lab',
                'description' => 'Practice clear concise structure for organized communication and explanations.',
                'mapped_skills' => ['clarity', 'answer structure'],
            ]),
            $this->module([
                'id' => 2,
                'title' => 'Advanced Technical Proof',
                'description' => 'Practice job evidence, technical tradeoffs, and role requirements.',
                'mapped_skills' => ['job evidence'],
            ]),
        ]);

        $algorithms = $result->algorithms->keyBy('key');

        $this->assertSame(7, $result->algorithm_count);
        $this->assertSame(7, $result->available_count);
        $this->assertTrue($algorithms->get('weighted_scoring')->available);
        $this->assertTrue($algorithms->get('decision_tree')->available);
        $this->assertTrue($algorithms->get('naive_bayes')->available);
        $this->assertTrue($algorithms->get('logistic_regression')->available);
        $this->assertTrue($algorithms->get('k_means')->available);
        $this->assertTrue($algorithms->get('random_forest')->available);
        $this->assertSame('Answer Clarity Lab', $algorithms->get('tfidf_cosine')->prediction);
        $this->assertNotEmpty($result->consensus_band);
    }

    public function test_it_returns_safe_unavailable_results_without_a_scored_target(): void
    {
        $suite = new ReadinessAlgorithmSuite;

        $result = $suite->analyze(null);

        $this->assertFalse($result->available);
        $this->assertSame(7, $result->algorithm_count);
        $this->assertSame(0, $result->available_count);
        $this->assertTrue($result->algorithms->every(fn ($algorithm): bool => $algorithm->available === false));
    }

    private function trainingScores(): array
    {
        return [
            $this->score([
                'id' => 1,
                'interview_session_id' => 101,
                'clarity_score' => 88,
                'relevance_score' => 91,
                'grammar_score' => 86,
                'professionalism_score' => 89,
                'confidence_score' => 84,
                'overall_readiness_score' => 89,
            ]),
            $this->score([
                'id' => 2,
                'interview_session_id' => 102,
                'clarity_score' => 82,
                'relevance_score' => 84,
                'grammar_score' => 80,
                'professionalism_score' => 83,
                'confidence_score' => 79,
                'overall_readiness_score' => 83,
            ]),
            $this->score([
                'id' => 3,
                'interview_session_id' => 103,
                'clarity_score' => 68,
                'relevance_score' => 70,
                'grammar_score' => 72,
                'professionalism_score' => 69,
                'confidence_score' => 65,
                'overall_readiness_score' => 69,
            ]),
            $this->score([
                'id' => 4,
                'interview_session_id' => 104,
                'clarity_score' => 62,
                'relevance_score' => 64,
                'grammar_score' => 68,
                'professionalism_score' => 65,
                'confidence_score' => 63,
                'overall_readiness_score' => 64,
            ]),
            $this->score([
                'id' => 5,
                'interview_session_id' => 105,
                'clarity_score' => 44,
                'relevance_score' => 48,
                'grammar_score' => 52,
                'professionalism_score' => 50,
                'confidence_score' => 45,
                'overall_readiness_score' => 48,
            ]),
            $this->score([
                'id' => 6,
                'interview_session_id' => 106,
                'clarity_score' => 38,
                'relevance_score' => 42,
                'grammar_score' => 48,
                'professionalism_score' => 43,
                'confidence_score' => 40,
                'overall_readiness_score' => 42,
            ]),
        ];
    }

    private function score(array $attributes): Score
    {
        $score = new Score;
        $score->setRawAttributes(array_merge([
            'readiness_band' => '',
            'delivery_stability_score' => 0,
            'job_evidence_match_score' => 0,
            'star_method_score' => 0,
        ], $attributes), true);

        return $score;
    }

    private function module(array $attributes): LearningModule
    {
        $module = new LearningModule;
        $module->setRawAttributes(array_merge([
            'type' => 'article',
            'category' => 'Interview Skills',
            'difficulty' => 'medium',
            'career_path' => '',
            'status' => 'published',
            'views' => 0,
            'is_featured' => false,
            'mapped_skills' => [],
        ], $attributes), true);

        return $module;
    }
}
