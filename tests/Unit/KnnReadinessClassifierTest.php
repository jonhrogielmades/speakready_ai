<?php

namespace Tests\Unit;

use App\Models\Score;
use App\Services\KnnReadinessClassifier;
use PHPUnit\Framework\TestCase;

class KnnReadinessClassifierTest extends TestCase
{
    public function test_it_predicts_readiness_from_nearest_neighbors(): void
    {
        $classifier = new KnnReadinessClassifier;
        $target = $this->score([
            'id' => 50,
            'interview_session_id' => 500,
            'clarity_score' => 82,
            'relevance_score' => 84,
            'grammar_score' => 78,
            'professionalism_score' => 80,
            'overall_readiness_score' => 81,
        ]);

        $result = $classifier->classifyScore($target, [
            $this->score([
                'id' => 1,
                'interview_session_id' => 101,
                'clarity_score' => 84,
                'relevance_score' => 86,
                'grammar_score' => 80,
                'professionalism_score' => 82,
                'overall_readiness_score' => 86,
                'readiness_band' => 'Ready for Simulation',
            ]),
            $this->score([
                'id' => 2,
                'interview_session_id' => 102,
                'clarity_score' => 80,
                'relevance_score' => 83,
                'grammar_score' => 79,
                'professionalism_score' => 78,
                'overall_readiness_score' => 83,
                'readiness_band' => 'Ready for Simulation',
            ]),
            $this->score([
                'id' => 3,
                'interview_session_id' => 103,
                'clarity_score' => 62,
                'relevance_score' => 61,
                'grammar_score' => 70,
                'professionalism_score' => 66,
                'overall_readiness_score' => 64,
                'readiness_band' => 'Nearly Ready',
            ]),
            $this->score([
                'id' => 4,
                'interview_session_id' => 104,
                'clarity_score' => 42,
                'relevance_score' => 44,
                'grammar_score' => 50,
                'professionalism_score' => 48,
                'overall_readiness_score' => 45,
                'readiness_band' => 'Developing',
            ]),
        ], 3);

        $this->assertTrue($result->available);
        $this->assertSame('knn_inverse_distance', $result->source);
        $this->assertSame(3, $result->neighbors_used);
        $this->assertSame('Ready for Simulation', $result->predicted_band);
        $this->assertGreaterThanOrEqual(80, $result->predicted_score);
        $this->assertArrayHasKey('Ready for Simulation', $result->label_votes);
    }

    public function test_it_uses_normalized_weighted_euclidean_distance(): void
    {
        $classifier = new KnnReadinessClassifier;
        $target = $this->score([
            'id' => 10,
            'interview_session_id' => 110,
            'clarity_score' => 70,
            'relevance_score' => 70,
            'overall_readiness_score' => 70,
        ]);
        $candidate = $this->score([
            'id' => 11,
            'interview_session_id' => 111,
            'clarity_score' => 60,
            'relevance_score' => 70,
            'overall_readiness_score' => 68,
        ]);

        $result = $classifier->classifyScore($target, [$candidate], 1);

        $this->assertTrue($result->available);
        $this->assertEqualsWithDelta(0.066332, $result->nearest_neighbors[0]['distance'], 0.000001);
        $this->assertSame(
            'sqrt(sum(weight_i * (target_i - neighbor_i)^2) / sum(weight_i))',
            $result->formula
        );
    }

    public function test_it_returns_unavailable_result_when_training_data_is_missing(): void
    {
        $classifier = new KnnReadinessClassifier;
        $target = $this->score([
            'id' => 20,
            'interview_session_id' => 120,
            'clarity_score' => 61,
            'relevance_score' => 62,
            'overall_readiness_score' => 62,
        ]);

        $result = $classifier->classifyScore($target, [], 5);

        $this->assertFalse($result->available);
        $this->assertSame('insufficient_training_examples', $result->source);
        $this->assertSame('Nearly Ready', $result->predicted_band);
        $this->assertSame(0, $result->neighbors_used);
        $this->assertSame('Unavailable', $result->reliability_band);
    }

    private function score(array $attributes): Score
    {
        $score = new Score;
        $score->setRawAttributes(array_merge([
            'readiness_band' => '',
        ], $attributes), true);

        return $score;
    }
}
