<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

    protected $table = 'learning_progress';

    protected $fillable = [
        'user_id',
        'learning_module_id',
        'status',
        'progress_percentage',
        'quiz_score',
        'learning_hours',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }
