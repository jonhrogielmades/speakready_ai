<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProviderEvaluationRun extends Model
{
    protected $fillable = [
        'benchmark_version',
        'status',
        'started_at',
        'completed_at',
        'provider_count',
        'case_count',
        'summary',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'provider_count' => 'integer',
        'case_count' => 'integer',
        'summary' => 'array',
    ];

    public function results()
    {
        return $this->hasMany(AiProviderEvaluationResult::class, 'run_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
