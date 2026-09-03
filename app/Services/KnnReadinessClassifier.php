<?php

namespace App\Services;

use App\Models\Score;
use Illuminate\Support\Collection;

class KnnReadinessClassifier
{
    public const DEFAULT_K = 5;

    private const MAX_K = 25;
    private const MAX_TRAINING_ROWS = 300;
    private const MIN_COMMON_FEATURES = 2;
    private const EPSILON = 0.001;

    private const FEATURE_WEIGHTS = [
        'clarity_score' => 0.22,
        'relevance_score' => 0.28,
        'grammar_score' => 0.10,
        'professionalism_score' => 0.16,
        'confidence_score' => 0.08,
        'delivery_stability_score' => 0.06,
        'job_evidence_match_score' => 0.06,
        'star_method_score' => 0.04,
    ];

    private const CORE_FEATURES = [
        'clarity_score',
        'relevance_score',
        'grammar_score',
        'professionalism_score',
    ];

    public function classifyForUser(int $userId, int $k = self::DEFAULT_K): object
    {
        $target = $this->latestScoreForUser($userId);

        if (! $target) {
            return $this->unavailable(
                'no_scored_session',
                'Complete one scored interview before KNN readiness matching can run.',
                null,
                $k
            );
        }

        return $this->classifyScore($target, $this->trainingScores($target), $k);
    }

    public function classifyScore(Score $target, iterable $trainingScores, int $k = self::DEFAULT_K): object
    {
        $k = $this->normalizeK($k);
        $targetFeatures = $this->featuresFromScore($target);

        if (count($targetFeatures) < self::MIN_COMMON_FEATURES) {
            return $this->unavailable(
                'insufficient_target_features',
                'KNN needs at least two usable score features for the latest interview.',
                $target,
                $k
            );
        }

        $trainingCollection = collect($trainingScores)->filter(fn ($score): bool => $score instanceof Score)->values();
        $neighbors = $trainingCollection
            ->map(fn (Score $candidate): ?array => $this->neighborFor($targetFeatures, $candidate))
            ->filter()
            ->sortBy([
                ['distance', 'asc'],
                ['score_id', 'asc'],
            ])
            ->values();

        if ($neighbors->isEmpty()) {
            return $this->unavailable(
                'insufficient_training_examples',
                'KNN needs other scored sessions with matching score features before it can compare readiness.',
                $target,
                $k,
                $trainingCollection->count()
            );
        }

        $selected = $neighbors->take($k)->values();
        $voteTotals = [];
        $weightedScore = 0.0;
        $totalWeight = 0.0;

        foreach ($selected as $neighbor) {
            $weight = 1 / (((float) $neighbor['distance']) + self::EPSILON);
            $band = (string) $neighbor['readiness_band'];
            $voteTotals[$band] = ($voteTotals[$band] ?? 0.0) + $weight;
            $weightedScore += ((int) $neighbor['overall_readiness_score']) * $weight;
            $totalWeight += $weight;
        }

        $predictedScore = $totalWeight > 0
            ? $this->clampInt((int) round($weightedScore / $totalWeight))
            : $this->overallScoreFor($target);
        $confidence = $this->confidenceFor($selected, $voteTotals, $k, count($targetFeatures));
        $predictedBand = $this->bandForScore($predictedScore);

        return (object) [
            'available' => true,
            'algorithm' => 'K-Nearest Neighbors',
            'source' => 'knn_inverse_distance',
            'formula' => 'sqrt(sum(weight_i * (target_i - neighbor_i)^2) / sum(weight_i))',
            'k_requested' => $k,
            'neighbors_used' => $selected->count(),
            'training_examples' => $trainingCollection->count(),
            'target_score_id' => $target->getKey(),
            'target_session_id' => $target->interview_session_id,
            'predicted_score' => $predictedScore,
            'predicted_band' => $predictedBand,
            'confidence' => $confidence,
            'reliability_band' => $this->reliabilityBand($confidence),
            'label_votes' => $this->roundedVotes($voteTotals),
            'nearest_neighbors' => $selected->take(5)->values()->all(),
            'message' => "KNN compared your latest scored interview with {$selected->count()} similar scored sessions and predicts {$predictedBand}.",
        ];
    }

    private function latestScoreForUser(int $userId): ?Score
    {
        return Score::query()
            ->select('scores.*')
            ->join('interview_sessions as latest_sessions', 'latest_sessions.id', '=', 'scores.interview_session_id')
            ->with(['session.category'])
            ->where('latest_sessions.user_id', $userId)
            ->where('latest_sessions.status', 'completed')
            ->whereNotNull('scores.overall_readiness_score')
            ->readinessEligible()
            ->orderByDesc('latest_sessions.created_at')
            ->orderByDesc('scores.id')
            ->first();
    }

    private function trainingScores(Score $target): Collection
    {
        return Score::query()
            ->select('scores.*')
            ->with(['session.category'])
            ->where('scores.id', '!=', $target->getKey())
            ->whereNotNull('scores.overall_readiness_score')
            ->whereHas('session', fn ($query) => $query->where('interview_sessions.status', 'completed'))
            ->readinessEligible()
            ->orderByDesc('scores.created_at')
            ->limit(self::MAX_TRAINING_ROWS)
            ->get();
    }

    private function neighborFor(array $targetFeatures, Score $candidate): ?array
    {
        $candidateFeatures = $this->featuresFromScore($candidate);
        $distance = $this->distance($targetFeatures, $candidateFeatures);

        if ($distance === null) {
            return null;
        }

        $overall = $this->overallScoreFor($candidate);

        return [
            'score_id' => $candidate->getKey(),
            'session_id' => $candidate->interview_session_id,
            'distance' => round($distance, 6),
            'similarity' => $this->clampInt((int) round((1 - min(1, $distance)) * 100)),
            'overall_readiness_score' => $overall,
            'readiness_band' => $this->readinessBandFor($candidate),
            'matching_features' => count(array_intersect_key($targetFeatures, $candidateFeatures)),
        ];
    }

    private function featuresFromScore(Score $score): array
    {
        $features = [];

        foreach (self::FEATURE_WEIGHTS as $field => $weight) {
            $value = $score->getAttribute($field);

            if (! is_numeric($value)) {
                continue;
            }

            $normalized = $this->clampInt((int) round((float) $value));
            if (! in_array($field, self::CORE_FEATURES, true) && $normalized === 0) {
                continue;
            }

            $features[$field] = $normalized / 100;
        }

        return $features;
    }

    private function distance(array $targetFeatures, array $candidateFeatures): ?float
    {
        $common = array_intersect_key($targetFeatures, $candidateFeatures);

        if (count($common) < self::MIN_COMMON_FEATURES) {
            return null;
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($common as $field => $targetValue) {
            $weight = self::FEATURE_WEIGHTS[$field] ?? 0.0;
            if ($weight <= 0) {
                continue;
            }

            $difference = $targetValue - $candidateFeatures[$field];
            $weightedSum += $weight * ($difference ** 2);
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0) {
            return null;
        }

        return sqrt($weightedSum / $weightTotal);
    }

    private function confidenceFor(Collection $neighbors, array $voteTotals, int $k, int $targetFeatureCount): int
    {
        if ($neighbors->isEmpty()) {
            return 0;
        }

        $neighborFactor = min(1.0, $neighbors->count() / max(1, $k));
        $averageDistance = (float) $neighbors->avg('distance');
        $closeness = max(0.0, min(1.0, 1 - $averageDistance));
        $coverage = $neighbors
            ->map(fn (array $neighbor): float => ((int) $neighbor['matching_features']) / max(1, $targetFeatureCount))
            ->avg() ?? 0.0;
        $voteValues = array_values($voteTotals);
        rsort($voteValues, SORT_NUMERIC);
        $topVote = (float) ($voteValues[0] ?? 0);
        $secondVote = (float) ($voteValues[1] ?? 0);
        $voteTotal = array_sum($voteValues);
        $voteMargin = $voteTotal > 0 ? max(0.0, ($topVote - $secondVote) / $voteTotal) : 0.0;

        $confidence = (int) round(
            ($neighborFactor * 0.25 + $closeness * 0.35 + $voteMargin * 0.20 + $coverage * 0.20) * 100
        );

        if ($neighbors->count() < 3) {
            $confidence = min($confidence, 55);
        }

        return $this->clampInt($confidence);
    }

    private function roundedVotes(array $voteTotals): array
    {
        arsort($voteTotals, SORT_NUMERIC);

        return collect($voteTotals)
            ->map(fn (float $value): float => round($value, 4))
            ->all();
    }

    private function readinessBandFor(Score $score): string
    {
        $band = trim((string) ($score->readiness_band ?? ''));

        return in_array($band, ['Ready for Simulation', 'Nearly Ready', 'Developing'], true)
            ? $band
            : $this->bandForScore($this->overallScoreFor($score));
    }

    private function bandForScore(int $score): string
    {
        return $score >= 80 ? 'Ready for Simulation' : ($score >= 60 ? 'Nearly Ready' : 'Developing');
    }

    private function overallScoreFor(Score $score): int
    {
        return $this->clampInt((int) round((float) ($score->overall_readiness_score ?? 0)));
    }

    private function reliabilityBand(int $confidence): string
    {
        return $confidence >= 80 ? 'High' : ($confidence >= 60 ? 'Moderate' : 'Limited');
    }

    private function unavailable(string $reason, string $message, ?Score $target, int $k, int $trainingExamples = 0): object
    {
        $k = $this->normalizeK($k);

        return (object) [
            'available' => false,
            'algorithm' => 'K-Nearest Neighbors',
            'source' => $reason,
            'formula' => 'sqrt(sum(weight_i * (target_i - neighbor_i)^2) / sum(weight_i))',
            'k_requested' => $k,
            'neighbors_used' => 0,
            'training_examples' => $trainingExamples,
            'target_score_id' => $target?->getKey(),
            'target_session_id' => $target?->interview_session_id,
            'predicted_score' => $target ? $this->overallScoreFor($target) : null,
            'predicted_band' => $target ? $this->bandForScore($this->overallScoreFor($target)) : null,
            'confidence' => 0,
            'reliability_band' => 'Unavailable',
            'label_votes' => [],
            'nearest_neighbors' => [],
            'message' => $message,
        ];
    }

    private function normalizeK(int $k): int
    {
        return max(1, min(self::MAX_K, $k));
    }

    private function clampInt(int $value, int $minimum = 0, int $maximum = 100): int
    {
        return max($minimum, min($maximum, $value));
    }
}
