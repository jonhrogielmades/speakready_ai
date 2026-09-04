<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderEvaluationResult extends Model
{
    protected $fillable = [
        'run_id',
        'provider_id',
        'provider_key',
        'provider_name',
        'task_type',
        'case_key',
        'status',
        'response_time_ms',
        'quality_score',
        'reliability_score',
        'schema_score',
        'accuracy_score',
        'safety_score',
        'prompt_excerpt',
        'output_excerpt',
        'evidence',
        'error_message',
    ];

    protected $casts = [
        'response_time_ms' => 'integer',
        'quality_score' => 'integer',
        'reliability_score' => 'integer',
        'schema_score' => 'integer',
        'accuracy_score' => 'integer',
        'safety_score' => 'integer',
        'evidence' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(AiProviderEvaluationRun::class, 'run_id');
    }

    public function provider()
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }
}
