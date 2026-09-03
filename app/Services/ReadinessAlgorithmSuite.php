<?php

namespace App\Services;

use App\Models\LearningModule;
use App\Models\Score;
use App\Support\LearningModuleSchema;
use App\Support\ScoreSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ReadinessAlgorithmSuite
{
    private const MAX_TRAINING_ROWS = 300;
    private const FOREST_TREES = 9;
    private const FOREST_DEPTH = 3;
    private const EPSILON = 0.000001;

    private const FEATURES = [
        'clarity_score' => [
            'label' => 'Clarity',
            'weight' => 0.25,
            'terms' => ['clarity', 'clear', 'concise', 'structure', 'organized', 'communication', 'explain'],
            'core' => true,
        ],
        'relevance_score' => [
            'label' => 'Relevance',
            'weight' => 0.35,
            'terms' => ['relevance', 'relevant', 'question', 'alignment', 'focus', 'specific', 'targeted'],
            'core' => true,
        ],
        'grammar_score' => [
            'label' => 'Grammar',
            'weight' => 0.10,
            'terms' => ['grammar', 'sentence', 'language', 'fluency', 'word', 'english'],
            'core' => true,
        ],
        'professionalism_score' => [
            'label' => 'Professionalism',
            'weight' => 0.20,
            'terms' => ['professional', 'professionalism', 'tone', 'confidence', 'presence', 'etiquette'],
            'core' => true,
        ],
        'confidence_score' => [
            'label' => 'Confidence',
            'weight' => 0.08,
            'terms' => ['confidence', 'delivery', 'voice', 'speaking', 'pace', 'filler', 'rehearsal'],
            'core' => false,
        ],
        'delivery_stability_score' => [
            'label' => 'Delivery Stability',
            'weight' => 0.06,
            'terms' => ['delivery', 'stability', 'pace', 'pause', 'filler', 'voice', 'steady'],
            'core' => false,
        ],
        'job_evidence_match_score' => [
            'label' => 'Job Evidence Match',
            'weight' => 0.06,
            'terms' => ['job', 'role', 'evidence', 'resume', 'experience', 'skills', 'requirements'],
            'core' => false,
        ],
        'star_method_score' => [
            'label' => 'STAR Method',
            'weight' => 0.10,
            'terms' => ['star', 'situation', 'task', 'action', 'result', 'example', 'evidence', 'story'],
            'core' => false,
        ],
    ];

    public function forUser(int $userId): object
    {
        if (! Schema::hasTable('scores') || ! Schema::hasTable('interview_sessions')) {
            return $this->analyze(null);
        }

        ScoreSchema::ensure();
        LearningModuleSchema::ensure();

        $target = $this->latestScoreForUser($userId);

        if (! $target) {
            return $this->analyze(null, [], $this->publishedModules());
        }

        return $this->analyze($target, $this->trainingScores($target), $this->publishedModules());
    }

    public function forAdminOverview(): object
    {
        if (! Schema::hasTable('scores') || ! Schema::hasTable('interview_sessions')) {
            return $this->analyze(null);
        }

        ScoreSchema::ensure();
        LearningModuleSchema::ensure();

        $target = Score::query()
            ->select('scores.*')
            ->join('interview_sessions as latest_algorithm_sessions', 'latest_algorithm_sessions.id', '=', 'scores.interview_session_id')
            ->with(['session.category'])
            ->where('latest_algorithm_sessions.status', 'completed')
            ->whereNotNull('scores.overall_readiness_score')
            ->readinessEligible()
            ->orderByDesc('latest_algorithm_sessions.created_at')
            ->orderByDesc('scores.id')
            ->first();

        if (! $target) {
            return $this->analyze(null, [], $this->publishedModules());
        }

        return $this->analyze($target, $this->trainingScores($target), $this->publishedModules());
    }

    public function analyze(?Score $target, iterable $trainingScores = [], iterable $modules = []): object
    {
        $training = collect($trainingScores)->filter(fn ($score): bool => $score instanceof Score)->values();
        $moduleCollection = collect($modules)->filter(fn ($module): bool => $module instanceof LearningModule)->values();

        if (! $target) {
            $algorithms = collect([
                $this->unavailable('weighted_scoring', 'Weighted Scoring', 'classification', 'fa-scale-balanced', 'No scored interview is available yet.'),
                $this->unavailable('decision_tree', 'Decision Tree', 'classification', 'fa-code-branch', 'No scored interview is available yet.'),
                $this->unavailable('naive_bayes', 'Naive Bayes', 'classification', 'fa-table-cells', 'No scored interview is available yet.'),
                $this->unavailable('logistic_regression', 'Logistic Regression', 'probability', 'fa-chart-line', 'No scored interview is available yet.'),
                $this->unavailable('k_means', 'K-Means Clustering', 'cluster', 'fa-circle-nodes', 'No scored interview is available yet.'),
                $this->unavailable('random_forest', 'Random Forest', 'classification', 'fa-tree', 'No scored interview is available yet.'),
                $this->unavailable('tfidf_cosine', 'TF-IDF Cosine Similarity', 'recommendation', 'fa-magnifying-glass-chart', 'No scored interview is available yet.'),
            ]);

            return $this->suiteResult($algorithms, $training->count());
        }

        $algorithms = collect([
            $this->weightedScoring($target),
            $this->decisionTree($target),
            $this->naiveBayes($target, $training),
            $this->logisticRegression($target, $training),
            $this->kMeans($target, $training),
            $this->randomForest($target, $training),
            $this->tfidfCosineSimilarity($target, $moduleCollection),
        ]);

        return $this->suiteResult($algorithms, $training->count());
    }

    private function latestScoreForUser(int $userId): ?Score
    {
        return Score::query()
            ->select('scores.*')
            ->join('interview_sessions as latest_algorithm_sessions', 'latest_algorithm_sessions.id', '=', 'scores.interview_session_id')
            ->with(['session.category'])
            ->where('latest_algorithm_sessions.user_id', $userId)
            ->where('latest_algorithm_sessions.status', 'completed')
            ->whereNotNull('scores.overall_readiness_score')
            ->readinessEligible()
            ->orderByDesc('latest_algorithm_sessions.created_at')
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

    private function publishedModules(): Collection
    {
        if (! Schema::hasTable('learning_modules')) {
            return collect();
        }

        return LearningModule::where('status', 'published')
            ->orderByDesc('is_featured')
            ->orderByDesc('views')
            ->orderBy('title')
            ->get();
    }

    private function weightedScoring(Score $target): object
    {
        $weighted = $this->weightedScore($target);

        if (! $weighted) {
            return $this->unavailable(
                'weighted_scoring',
                'Weighted Scoring',
                'classification',
                'fa-scale-balanced',
                'Weighted scoring needs at least two score metrics.'
            );
        }

        return $this->result(
            key: 'weighted_scoring',
            name: 'Weighted Scoring',
            type: 'classification',
            icon: 'fa-scale-balanced',
            prediction: $weighted['band'],
            score: $weighted['score'],
            confidence: $this->clampInt(78 + min(12, $weighted['features_used'] * 2)),
            reliability: 'High',
            formula: '(sum(score_i * weight_i)) / sum(weight_i)',
            purpose: 'Computes the main readiness score from rubric metrics.',
            message: "Weighted scoring predicts {$weighted['band']} at {$weighted['score']}%."
        );
    }

    private function decisionTree(Score $target): object
    {
        $weighted = $this->weightedScore($target);

        if (! $weighted) {
            return $this->unavailable(
                'decision_tree',
                'Decision Tree',
                'classification',
                'fa-code-branch',
                'Decision tree needs enough score metrics to evaluate readiness rules.'
            );
        }

        $overall = $weighted['score'];
        $clarity = $this->scoreValue($target, 'clarity_score') ?? $overall;
        $relevance = $this->scoreValue($target, 'relevance_score') ?? $overall;
        $professionalism = $this->scoreValue($target, 'professionalism_score') ?? $overall;

        if ($overall >= 80 && $clarity >= 70 && $relevance >= 70 && $professionalism >= 70) {
            $band = 'Ready for Simulation';
            $rule = 'overall >= 80 and core skills >= 70';
        } elseif ($overall >= 60 && ($clarity >= 55 || $relevance >= 55)) {
            $band = 'Nearly Ready';
            $rule = 'overall >= 60 with at least one core skill >= 55';
        } else {
            $band = 'Developing';
            $rule = 'overall below readiness thresholds';
        }

        $margin = min(abs($overall - 80), abs($overall - 60));
        $confidence = $this->clampInt(68 + min(22, $margin));

        return $this->result(
            key: 'decision_tree',
            name: 'Decision Tree',
            type: 'classification',
            icon: 'fa-code-branch',
            prediction: $band,
            score: $overall,
            confidence: $confidence,
            reliability: $this->reliabilityBand($confidence),
            formula: 'IF/ELSE threshold rules over readiness and core metrics',
            purpose: 'Classifies readiness with transparent rules.',
            message: "Decision tree predicts {$band} using rule: {$rule}.",
            meta: ['rule' => $rule]
        );
    }

    private function naiveBayes(Score $target, Collection $training): object
    {
        $targetFeatures = $this->sparseFeatures($target);
        $rows = $this->trainingRows($training, sparse: true);

        if (count($targetFeatures) < 2 || $rows->count() < 3 || $rows->pluck('label')->unique()->count() < 2) {
            return $this->unavailable(
                'naive_bayes',
                'Naive Bayes',
                'classification',
                'fa-table-cells',
                'Naive Bayes needs at least three historical scores across two readiness bands.'
            );
        }

        $groups = $rows->groupBy('label');
        $logScores = [];
        $classScoreAverages = [];
        $usedFeatureCount = 0;

        foreach ($groups as $label => $group) {
            $logScore = log($group->count() / max(1, $rows->count()));
            $classScoreAverages[$label] = (int) round($group->avg('overall'));

            foreach ($targetFeatures as $field => $value) {
                $values = $group
                    ->map(fn (array $row) => $row['features'][$field] ?? null)
                    ->filter(fn ($item): bool => is_numeric($item))
                    ->values();

                if ($values->isEmpty()) {
                    continue;
                }

                $mean = (float) $values->avg();
                $variance = max(0.0025, $values
                    ->map(fn ($item): float => (((float) $item - $mean) ** 2))
                    ->avg() ?? 0.0025);

                $logScore += -0.5 * log(2 * M_PI * $variance)
                    - ((($value - $mean) ** 2) / (2 * $variance));
                $usedFeatureCount++;
            }

            $logScores[$label] = $logScore;
        }

        if ($usedFeatureCount < 2) {
            return $this->unavailable(
                'naive_bayes',
                'Naive Bayes',
                'classification',
                'fa-table-cells',
                'Naive Bayes could not find enough matching historical score features.'
            );
        }

        $probabilities = $this->softmax($logScores);
        arsort($probabilities, SORT_NUMERIC);
        $band = (string) array_key_first($probabilities);
        $confidence = $this->clampInt((int) round(((float) reset($probabilities)) * 100));

        if ($rows->count() < 8) {
            $confidence = min($confidence, 72);
        }

        return $this->result(
            key: 'naive_bayes',
            name: 'Naive Bayes',
            type: 'classification',
            icon: 'fa-table-cells',
            prediction: $band,
            score: $classScoreAverages[$band] ?? $this->overallScore($target),
            confidence: $confidence,
            reliability: $this->reliabilityBand($confidence),
            formula: 'argmax P(class) * product P(feature_i | class)',
            purpose: 'Predicts readiness band from historical metric distributions.',
            message: "Naive Bayes predicts {$band} from {$rows->count()} historical scores.",
            meta: ['probabilities' => $this->rounded($probabilities)]
        );
    }

    private function logisticRegression(Score $target, Collection $training): object
    {
        $rows = $this->trainingRows($training, sparse: false);

        if ($rows->count() < 4) {
            return $this->unavailable(
                'logistic_regression',
                'Logistic Regression',
                'probability',
                'fa-chart-line',
                'Logistic regression needs at least four historical scores.'
            );
        }

        $positive = $rows->filter(fn (array $row): bool => $row['ready'] === 1)->count();
        $negative = $rows->count() - $positive;

        if ($positive === 0 || $negative === 0) {
            return $this->unavailable(
                'logistic_regression',
                'Logistic Regression',
                'probability',
                'fa-chart-line',
                'Logistic regression needs both ready and not-ready examples.'
            );
        }

        $fields = array_keys(self::FEATURES);
        $weights = array_fill_keys($fields, 0.0);
        $bias = log($positive / max(1, $negative));
        $learningRate = 0.28;
        $regularization = 0.003;

        for ($epoch = 0; $epoch < 160; $epoch++) {
            foreach ($rows as $row) {
                $features = $row['features'];
                $prediction = $this->sigmoid($bias + $this->dot($weights, $features));
                $error = $prediction - $row['ready'];
                $bias -= $learningRate * $error;

                foreach ($fields as $field) {
                    $weights[$field] -= $learningRate * (($error * ($features[$field] ?? 0.0)) + ($regularization * $weights[$field]));
                }
            }

            $learningRate *= 0.985;
        }

        $targetFeatures = $this->denseFeatures($target);
        $probability = $this->sigmoid($bias + $this->dot($weights, $targetFeatures));
        $readyPercent = $this->clampInt((int) round($probability * 100));
        $band = $readyPercent >= 60
            ? 'Ready for Simulation'
            : ($readyPercent >= 35 ? 'Nearly Ready' : 'Developing');
        $confidence = $this->clampInt((int) round(55 + abs($probability - 0.5) * 80 + min(10, $rows->count())));

        return $this->result(
            key: 'logistic_regression',
            name: 'Logistic Regression',
            type: 'probability',
            icon: 'fa-chart-line',
            prediction: "{$readyPercent}% ready probability",
            score: $readyPercent,
            confidence: $confidence,
            reliability: $this->reliabilityBand($confidence),
            formula: '1 / (1 + e^-(b0 + b1x1 + ... + bnxn))',
            purpose: 'Estimates the probability that the latest score pattern is ready.',
            message: "Logistic regression estimates {$readyPercent}% ready probability.",
            meta: ['predicted_band' => $band]
        );
    }

    private function kMeans(Score $target, Collection $training): object
    {
        $rows = $this->trainingRows($training, sparse: false);

        if ($rows->count() < 3) {
            return $this->unavailable(
                'k_means',
                'K-Means Clustering',
                'cluster',
                'fa-circle-nodes',
                'K-Means needs at least three historical scores.'
            );
        }

        $sorted = $rows->sortBy('overall')->values();
        $centroids = [
            $sorted->first()['features'],
            $sorted->get((int) floor(($sorted->count() - 1) / 2))['features'],
            $sorted->last()['features'],
        ];
        $assignments = [];

        for ($iteration = 0; $iteration < 12; $iteration++) {
            $assignments = [[], [], []];

            foreach ($rows as $row) {
                $cluster = $this->nearestCentroid($row['features'], $centroids)['index'];
                $assignments[$cluster][] = $row;
            }

            foreach ($assignments as $cluster => $items) {
                if ($items === []) {
                    continue;
                }

                $centroids[$cluster] = $this->averageVector(collect($items)->pluck('features')->all());
            }
        }

        $targetMatch = $this->nearestCentroid($this->denseFeatures($target), $centroids);
        $clusterRows = collect($assignments[$targetMatch['index']] ?? []);
        $clusterAverage = $clusterRows->isNotEmpty()
            ? $this->clampInt((int) round($clusterRows->avg('overall')))
            : $this->overallScore($target);
        $band = $this->bandForScore($clusterAverage);
        $distanceGap = $targetMatch['second_distance'] > 0
            ? ($targetMatch['second_distance'] - $targetMatch['distance']) / $targetMatch['second_distance']
            : 1.0;
        $confidence = $this->clampInt((int) round(52 + max(0, $distanceGap) * 38 + min(10, $clusterRows->count())));

        return $this->result(
            key: 'k_means',
            name: 'K-Means Clustering',
            type: 'cluster',
            icon: 'fa-circle-nodes',
            prediction: $band,
            score: $clusterAverage,
            confidence: $confidence,
            reliability: $this->reliabilityBand($confidence),
            formula: 'assign to nearest centroid, then update centroid means',
            purpose: 'Groups users with similar readiness score patterns.',
            message: "K-Means places this score pattern in a {$band} cluster.",
            meta: [
                'cluster' => $targetMatch['index'] + 1,
                'cluster_size' => $clusterRows->count(),
            ]
        );
    }

    private function randomForest(Score $target, Collection $training): object
    {
        $rows = $this->trainingRows($training, sparse: false);

        if ($rows->count() < 5 || $rows->pluck('label')->unique()->count() < 2) {
            return $this->unavailable(
                'random_forest',
                'Random Forest',
                'classification',
                'fa-tree',
                'Random Forest needs at least five historical scores across two readiness bands.'
            );
        }

        $targetFeatures = $this->denseFeatures($target);
        $votes = [];
        $scores = [];

        for ($tree = 0; $tree < self::FOREST_TREES; $tree++) {
            $sample = $this->bootstrapRows($rows->all(), $tree);
            $features = $this->forestFeatureSubset($tree);
            $model = $this->buildTree($sample, $features, self::FOREST_DEPTH);
            $prediction = $this->predictTree($model, $targetFeatures);
            $votes[$prediction['label']] = ($votes[$prediction['label']] ?? 0) + 1;
            $scores[] = $prediction['score'];
        }

        arsort($votes, SORT_NUMERIC);
        $band = (string) array_key_first($votes);
        $score = $this->clampInt((int) round(collect($scores)->avg() ?? $this->overallScore($target)));
        $topVotes = (int) reset($votes);
        $confidence = $this->clampInt((int) round(($topVotes / self::FOREST_TREES) * 100));

        if ($rows->count() < 12) {
            $confidence = min($confidence, 78);
        }

        return $this->result(
            key: 'random_forest',
            name: 'Random Forest',
            type: 'classification',
            icon: 'fa-tree',
            prediction: $band,
            score: $score,
            confidence: $confidence,
            reliability: $this->reliabilityBand($confidence),
            formula: 'majority vote from multiple bootstrapped decision trees',
            purpose: 'Combines several tree predictions for a steadier readiness estimate.',
            message: "Random Forest predicts {$band} from ".self::FOREST_TREES.' decision trees.',
            meta: ['votes' => $votes]
        );
    }

    private function tfidfCosineSimilarity(Score $target, Collection $modules): object
    {
        if ($modules->isEmpty()) {
            return $this->unavailable(
                'tfidf_cosine',
                'TF-IDF Cosine Similarity',
                'recommendation',
                'fa-magnifying-glass-chart',
                'TF-IDF needs at least one published learning module.'
            );
        }

        $targetDocument = $this->weaknessDocument($target);

        if ($targetDocument === '') {
            return $this->unavailable(
                'tfidf_cosine',
                'TF-IDF Cosine Similarity',
                'recommendation',
                'fa-magnifying-glass-chart',
                'TF-IDF needs usable score weaknesses to build a search document.'
            );
        }

        $documents = collect([$targetDocument])
            ->merge($modules->map(fn (LearningModule $module): string => $this->moduleDocument($module)))
            ->values();
        $idf = $this->idf($documents);
        $targetVector = $this->tfidfVector($targetDocument, $idf);

        $matches = $modules
            ->map(function (LearningModule $module) use ($idf, $targetVector): array {
                $similarity = $this->cosine($targetVector, $this->tfidfVector($this->moduleDocument($module), $idf));

                return [
                    'module_id' => $module->getKey(),
                    'title' => (string) ($module->title ?? 'Learning module'),
                    'similarity' => $similarity,
                ];
            })
            ->sortByDesc('similarity')
            ->values();

        $best = $matches->first();

        if (! $best || $best['similarity'] <= 0) {
            return $this->unavailable(
                'tfidf_cosine',
                'TF-IDF Cosine Similarity',
                'recommendation',
                'fa-magnifying-glass-chart',
                'No learning module matched the current weakness terms.'
            );
        }

        $confidence = $this->clampInt((int) round(min(1, $best['similarity']) * 100));

        return $this->result(
            key: 'tfidf_cosine',
            name: 'TF-IDF Cosine Similarity',
            type: 'recommendation',
            icon: 'fa-magnifying-glass-chart',
            prediction: $best['title'],
            score: $confidence,
            confidence: max(35, $confidence),
            reliability: $this->reliabilityBand(max(35, $confidence)),
            formula: '(A dot B) / (||A|| * ||B||)',
            purpose: 'Matches weak skills to the most relevant learning module.',
            message: "TF-IDF recommends {$best['title']} for the current weak skill terms.",
            meta: [
                'module_id' => $best['module_id'],
                'similarity' => round($best['similarity'], 4),
                'top_matches' => $matches->take(3)->map(fn (array $match): array => [
                    'module_id' => $match['module_id'],
                    'title' => $match['title'],
                    'similarity' => round($match['similarity'], 4),
                ])->all(),
            ]
        );
    }

    private function suiteResult(Collection $algorithms, int $trainingExamples): object
    {
        $classification = $algorithms
            ->filter(fn (object $algorithm): bool => $algorithm->available && in_array($algorithm->type, ['classification', 'cluster'], true))
            ->values();
        $votes = [];

        foreach ($classification as $algorithm) {
            $band = (string) $algorithm->prediction;
            $votes[$band] = ($votes[$band] ?? 0) + 1;
        }

        arsort($votes, SORT_NUMERIC);
        $consensusBand = $votes === [] ? null : (string) array_key_first($votes);
        $availableCount = $algorithms->filter(fn (object $algorithm): bool => $algorithm->available)->count();

        return (object) [
            'available' => $availableCount > 0,
            'algorithm_count' => $algorithms->count(),
            'available_count' => $availableCount,
            'training_examples' => $trainingExamples,
            'consensus_band' => $consensusBand,
            'consensus_votes' => $votes,
            'algorithms' => $algorithms,
            'module_match' => $algorithms->firstWhere('key', 'tfidf_cosine'),
            'summary' => "Algorithm suite ran {$availableCount} of {$algorithms->count()} checks.",
        ];
    }

    private function trainingRows(Collection $scores, bool $sparse): Collection
    {
        return $scores
            ->map(function (Score $score) use ($sparse): ?array {
                $features = $sparse ? $this->sparseFeatures($score) : $this->denseFeatures($score);

                if (count($features) < 2) {
                    return null;
                }

                $overall = $this->overallScore($score);

                return [
                    'score' => $score,
                    'features' => $features,
                    'label' => $this->bandForScore($overall),
                    'ready' => $overall >= 80 ? 1 : 0,
                    'overall' => $overall,
                ];
            })
            ->filter()
            ->values();
    }

    private function sparseFeatures(Score $score): array
    {
        $features = [];

        foreach (self::FEATURES as $field => $config) {
            $value = $this->scoreValue($score, $field);

            if ($value === null) {
                continue;
            }

            if (! ($config['core'] ?? false) && $value === 0) {
                continue;
            }

            $features[$field] = $value / 100;
        }

        return $features;
    }

    private function denseFeatures(Score $score): array
    {
        $features = [];

        foreach (self::FEATURES as $field => $config) {
            $features[$field] = ($this->scoreValue($score, $field) ?? 0) / 100;
        }

        return $features;
    }

    private function weightedScore(Score $score): ?array
    {
        $sum = 0.0;
        $weightTotal = 0.0;
        $used = 0;

        foreach (self::FEATURES as $field => $config) {
            if (! in_array($field, ['clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score', 'star_method_score'], true)) {
                continue;
            }

            $value = $this->scoreValue($score, $field);

            if ($value === null || ($field === 'star_method_score' && $value === 0)) {
                continue;
            }

            $weight = (float) $config['weight'];
            $sum += $value * $weight;
            $weightTotal += $weight;
            $used++;
        }

        if ($weightTotal <= 0 || $used < 2) {
            return null;
        }

        $weighted = $this->clampInt((int) round($sum / $weightTotal));

        return [
            'score' => $weighted,
            'band' => $this->bandForScore($weighted),
            'features_used' => $used,
        ];
    }

    private function scoreValue(Score $score, string $field): ?int
    {
        $value = $score->getAttribute($field);

        if (! is_numeric($value)) {
            return null;
        }

        return $this->clampInt((int) round((float) $value));
    }

    private function overallScore(Score $score): int
    {
        return $this->clampInt((int) round((float) ($score->overall_readiness_score ?? 0)));
    }

    private function bandForScore(int $score): string
    {
        return $score >= 80 ? 'Ready for Simulation' : ($score >= 60 ? 'Nearly Ready' : 'Developing');
    }

    private function reliabilityBand(int $confidence): string
    {
        return $confidence >= 80 ? 'High' : ($confidence >= 60 ? 'Moderate' : 'Limited');
    }

    private function softmax(array $logScores): array
    {
        if ($logScores === []) {
            return [];
        }

        $max = max($logScores);
        $exp = [];
        $sum = 0.0;

        foreach ($logScores as $label => $score) {
            $value = exp($score - $max);
            $exp[$label] = $value;
            $sum += $value;
        }

        if ($sum <= 0) {
            return [];
        }

        foreach ($exp as $label => $value) {
            $exp[$label] = $value / $sum;
        }

        return $exp;
    }

    private function sigmoid(float $value): float
    {
        if ($value >= 35) {
            return 1.0;
        }

        if ($value <= -35) {
            return 0.0;
        }

        return 1 / (1 + exp(-$value));
    }

    private function dot(array $weights, array $features): float
    {
        $sum = 0.0;

        foreach ($weights as $field => $weight) {
            $sum += $weight * ($features[$field] ?? 0.0);
        }

        return $sum;
    }

    private function nearestCentroid(array $features, array $centroids): array
    {
        $distances = [];

        foreach ($centroids as $index => $centroid) {
            $distances[$index] = $this->squaredDistance($features, $centroid);
        }

        asort($distances, SORT_NUMERIC);
        $keys = array_keys($distances);

        return [
            'index' => (int) ($keys[0] ?? 0),
            'distance' => (float) ($distances[$keys[0] ?? 0] ?? 0.0),
            'second_distance' => (float) ($distances[$keys[1] ?? ($keys[0] ?? 0)] ?? 0.0),
        ];
    }

    private function squaredDistance(array $left, array $right): float
    {
        $sum = 0.0;

        foreach (array_keys(self::FEATURES) as $field) {
            $difference = ($left[$field] ?? 0.0) - ($right[$field] ?? 0.0);
            $sum += $difference ** 2;
        }

        return $sum;
    }

    private function averageVector(array $vectors): array
    {
        $average = array_fill_keys(array_keys(self::FEATURES), 0.0);
        $count = max(1, count($vectors));

        foreach ($vectors as $vector) {
            foreach ($average as $field => $value) {
                $average[$field] += $vector[$field] ?? 0.0;
            }
        }

        foreach ($average as $field => $value) {
            $average[$field] = $value / $count;
        }

        return $average;
    }

    private function bootstrapRows(array $rows, int $treeIndex): array
    {
        $count = count($rows);
        $sample = [];

        for ($index = 0; $index < $count; $index++) {
            $sampleIndex = (($treeIndex + 3) * 17 + ($index + 1) * 31 + ($treeIndex * $index * 7)) % $count;
            $sample[] = $rows[$sampleIndex];
        }

        return $sample;
    }

    private function forestFeatureSubset(int $treeIndex): array
    {
        $features = array_keys(self::FEATURES);
        $count = max(2, (int) ceil(sqrt(count($features))));
        $offset = $treeIndex % count($features);
        $rotated = array_merge(array_slice($features, $offset), array_slice($features, 0, $offset));

        return array_slice($rotated, 0, $count);
    }

    private function buildTree(array $rows, array $features, int $depth): array
    {
        if ($depth <= 0 || count($rows) < 2 || count(array_unique(array_column($rows, 'label'))) <= 1) {
            return $this->leaf($rows);
        }

        $split = $this->bestSplit($rows, $features);

        if (! $split) {
            return $this->leaf($rows);
        }

        return [
            'type' => 'split',
            'field' => $split['field'],
            'threshold' => $split['threshold'],
            'left' => $this->buildTree($split['left'], $features, $depth - 1),
            'right' => $this->buildTree($split['right'], $features, $depth - 1),
        ];
    }

    private function bestSplit(array $rows, array $features): ?array
    {
        $best = null;
        $bestGini = INF;

        foreach ($features as $field) {
            $values = collect($rows)
                ->map(fn (array $row): float => (float) ($row['features'][$field] ?? 0.0))
                ->unique()
                ->sort()
                ->values();

            if ($values->count() < 2) {
                continue;
            }

            for ($index = 0; $index < $values->count() - 1; $index++) {
                $threshold = (((float) $values[$index]) + ((float) $values[$index + 1])) / 2;
                $left = [];
                $right = [];

                foreach ($rows as $row) {
                    if (($row['features'][$field] ?? 0.0) <= $threshold) {
                        $left[] = $row;
                    } else {
                        $right[] = $row;
                    }
                }

                if ($left === [] || $right === []) {
                    continue;
                }

                $gini = ((count($left) / count($rows)) * $this->gini($left))
                    + ((count($right) / count($rows)) * $this->gini($right));

                if ($gini < $bestGini) {
                    $bestGini = $gini;
                    $best = [
                        'field' => $field,
                        'threshold' => $threshold,
                        'left' => $left,
                        'right' => $right,
                    ];
                }
            }
        }

        return $best;
    }

    private function gini(array $rows): float
    {
        if ($rows === []) {
            return 0.0;
        }

        $counts = array_count_values(array_column($rows, 'label'));
        $impurity = 1.0;

        foreach ($counts as $count) {
            $probability = $count / count($rows);
            $impurity -= $probability ** 2;
        }

        return $impurity;
    }

    private function leaf(array $rows): array
    {
        if ($rows === []) {
            return [
                'type' => 'leaf',
                'label' => 'Developing',
                'score' => 0,
            ];
        }

        $votes = array_count_values(array_column($rows, 'label'));
        arsort($votes, SORT_NUMERIC);

        return [
            'type' => 'leaf',
            'label' => (string) array_key_first($votes),
            'score' => $this->clampInt((int) round(collect($rows)->avg('overall') ?? 0)),
        ];
    }

    private function predictTree(array $tree, array $features): array
    {
        while (($tree['type'] ?? 'leaf') === 'split') {
            $field = (string) $tree['field'];
            $threshold = (float) $tree['threshold'];
            $tree = (($features[$field] ?? 0.0) <= $threshold) ? $tree['left'] : $tree['right'];
        }

        return [
            'label' => (string) ($tree['label'] ?? 'Developing'),
            'score' => $this->clampInt((int) round((float) ($tree['score'] ?? 0))),
        ];
    }

    private function weaknessDocument(Score $target): string
    {
        $values = collect(self::FEATURES)
            ->map(function (array $config, string $field) use ($target): ?array {
                $value = $this->scoreValue($target, $field);

                if ($value === null || (! ($config['core'] ?? false) && $value === 0)) {
                    return null;
                }

                return [
                    'field' => $field,
                    'score' => $value,
                    'terms' => $config['terms'],
                ];
            })
            ->filter()
            ->values();

        $weak = $values->filter(fn (array $item): bool => $item['score'] <= 78)->values();

        if ($weak->isEmpty()) {
            $weak = $values->sortBy('score')->take(2)->values();
        }

        return $weak
            ->flatMap(function (array $item): array {
                $repeat = $item['score'] <= 60 ? 3 : 2;

                return collect($item['terms'])->flatMap(fn (string $term): array => array_fill(0, $repeat, $term))->all();
            })
            ->implode(' ');
    }

    private function moduleDocument(LearningModule $module): string
    {
        $attributes = $module->getAttributes();
        $skills = $attributes['mapped_skills'] ?? [];

        if (is_string($skills)) {
            $decoded = json_decode($skills, true);
            $skills = is_array($decoded) ? $decoded : [$skills];
        }

        if (! is_array($skills)) {
            $skills = [];
        }

        return implode(' ', array_filter([
            $attributes['title'] ?? null,
            $attributes['description'] ?? null,
            $attributes['type'] ?? null,
            $attributes['category'] ?? null,
            $attributes['difficulty'] ?? null,
            $attributes['career_path'] ?? null,
            implode(' ', array_filter(array_map(fn ($skill): string => (string) $skill, $skills))),
        ]));
    }

    private function idf(Collection $documents): array
    {
        $documentCount = max(1, $documents->count());
        $frequency = [];

        foreach ($documents as $document) {
            foreach (array_unique($this->tokens($document)) as $token) {
                $frequency[$token] = ($frequency[$token] ?? 0) + 1;
            }
        }

        return collect($frequency)
            ->map(fn (int $count): float => log(($documentCount + 1) / ($count + 1)) + 1)
            ->all();
    }

    private function tfidfVector(string $document, array $idf): array
    {
        $tokens = $this->tokens($document);

        if ($tokens === []) {
            return [];
        }

        $counts = array_count_values($tokens);
        $total = count($tokens);
        $vector = [];

        foreach ($counts as $token => $count) {
            $vector[$token] = ($count / $total) * ($idf[$token] ?? 1.0);
        }

        return $vector;
    }

    private function cosine(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $dot = 0.0;
        foreach ($left as $token => $value) {
            $dot += $value * ($right[$token] ?? 0.0);
        }

        $leftNorm = sqrt(array_sum(array_map(fn (float $value): float => $value ** 2, $left)));
        $rightNorm = sqrt(array_sum(array_map(fn (float $value): float => $value ** 2, $right)));

        if ($leftNorm <= 0 || $rightNorm <= 0) {
            return 0.0;
        }

        return $dot / ($leftNorm * $rightNorm);
    }

    private function tokens(string $text): array
    {
        preg_match_all('/[a-z0-9][a-z0-9-]{1,}/i', mb_strtolower($text), $matches);
        $stopWords = ['the', 'and', 'for', 'with', 'your', 'this', 'that', 'from', 'into', 'you', 'are', 'can', 'how'];

        return collect($matches[0] ?? [])
            ->map(fn (string $token): string => trim($token, '-'))
            ->filter(fn (string $token): bool => $token !== '' && ! in_array($token, $stopWords, true))
            ->values()
            ->all();
    }

    private function result(
        string $key,
        string $name,
        string $type,
        string $icon,
        string $prediction,
        ?int $score,
        int $confidence,
        string $reliability,
        string $formula,
        string $purpose,
        string $message,
        array $meta = []
    ): object {
        return (object) [
            'key' => $key,
            'name' => $name,
            'type' => $type,
            'icon' => $icon,
            'available' => true,
            'prediction' => $prediction,
            'score' => $score,
            'confidence' => $this->clampInt($confidence),
            'reliability_band' => $reliability,
            'formula' => $formula,
            'purpose' => $purpose,
            'message' => $message,
            'meta' => $meta,
        ];
    }

    private function unavailable(string $key, string $name, string $type, string $icon, string $message): object
    {
        return (object) [
            'key' => $key,
            'name' => $name,
            'type' => $type,
            'icon' => $icon,
            'available' => false,
            'prediction' => 'Unavailable',
            'score' => null,
            'confidence' => 0,
            'reliability_band' => 'Unavailable',
            'formula' => '',
            'purpose' => '',
            'message' => $message,
            'meta' => [],
        ];
    }

    private function rounded(array $values): array
    {
        return collect($values)
            ->map(fn (float $value): float => round($value, 4))
            ->all();
    }

    private function clampInt(int $value, int $minimum = 0, int $maximum = 100): int
    {
        return max($minimum, min($maximum, $value));
    }
}
