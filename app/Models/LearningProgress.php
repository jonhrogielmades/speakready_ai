<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $chapter_gate_seconds
 * @property \Illuminate\Support\Carbon|null $chapter_gate_last_heartbeat_at
 * @property array<string, mixed>|null $quiz_results
 */
class LearningProgress extends Model
{
    use HasFactory;

    protected $table = 'learning_progress';

    protected $fillable = [
        'user_id',
        'learning_module_id',
        'status',
        'progress_percentage',
        'quiz_score',
        'learning_hours',
        'chapter_gate_seconds',
        'chapter_gate_last_heartbeat_at',
        'quiz_results',
    ];

    protected $casts = [
        'chapter_gate_last_heartbeat_at' => 'datetime',
        'quiz_results' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }
}
