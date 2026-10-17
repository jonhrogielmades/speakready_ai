<?php

namespace Tests\Unit;

use App\Services\QuestionRecommendationService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class QuestionRecommendationModelQualityTest extends TestCase
{
    private string $modelPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelPath = storage_path('framework/testing/question-reranker-quality');
        File::ensureDirectoryExists($this->modelPath);
        foreach (['config.json', 'tokenizer.json', 'model.safetensors'] as $file) {
            file_put_contents($this->modelPath.DIRECTORY_SEPARATOR.$file, '{}');
        }
        @unlink($this->modelPath.DIRECTORY_SEPARATOR.'validation_metrics.json');

        config([
            'services.question_recommender.trained_model_enabled' => true,
            'services.question_recommender.trained_model_path' => $this->modelPath,
            'services.question_recommender.trained_model_validation_path' => $this->modelPath.DIRECTORY_SEPARATOR.'validation_metrics.json',
            'services.question_recommender.trained_model_require_validation' => true,
            'services.question_recommender.trained_model_min_evaluation_examples' => 200,
            'services.question_recommender.trained_model_min_precision_at_5' => 0.95,
            'services.question_recommender.rerank_script' => 'scripts/rerank_question_candidates.py',
        ]);
    }

    public function test_unvalidated_question_reranker_is_not_available(): void
    {
        $service = app(QuestionRecommendationService::class);

        $this->assertFalse($service->trainedModelAvailable());
        $this->assertSame(['validation_metrics_missing'], $service->trainedModelQuality()['reasons']);
    }

    public function test_only_held_out_reranker_metrics_above_threshold_enable_model(): void
    {
        file_put_contents($this->modelPath.DIRECTORY_SEPARATOR.'validation_metrics.json', json_encode([
            'schema_version' => 1,
            'data_split' => 'held_out',
            'evaluation_examples' => 500,
            'model_sha256' => hash_file('sha256', $this->modelPath.DIRECTORY_SEPARATOR.'model.safetensors'),
            'metrics' => ['precision_at_5' => 0.97],
        ]));
        $service = app(QuestionRecommendationService::class);

        $this->assertTrue($service->trainedModelAvailable());
        $this->assertTrue($service->trainedModelQuality()['production_ready']);
    }

    public function test_high_training_score_without_held_out_split_is_rejected(): void
    {
        file_put_contents($this->modelPath.DIRECTORY_SEPARATOR.'validation_metrics.json', json_encode([
            'schema_version' => 1,
            'data_split' => 'training',
            'evaluation_examples' => 500,
            'model_sha256' => hash_file('sha256', $this->modelPath.DIRECTORY_SEPARATOR.'model.safetensors'),
            'metrics' => ['precision_at_5' => 0.99],
        ]));
        $service = app(QuestionRecommendationService::class);

        $this->assertFalse($service->trainedModelAvailable());
        $this->assertContains('held_out_split_not_confirmed', $service->trainedModelQuality()['reasons']);
    }

    public function test_validation_metrics_for_a_different_model_are_rejected(): void
    {
        file_put_contents($this->modelPath.DIRECTORY_SEPARATOR.'validation_metrics.json', json_encode([
            'schema_version' => 1,
            'data_split' => 'held_out',
            'evaluation_examples' => 500,
            'model_sha256' => str_repeat('0', 64),
            'metrics' => ['precision_at_5' => 0.99],
        ]));
        $service = app(QuestionRecommendationService::class);

        $this->assertFalse($service->trainedModelAvailable());
        $this->assertContains('validation_model_hash_mismatch', $service->trainedModelQuality()['reasons']);
    }
}
