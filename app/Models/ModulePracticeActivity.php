<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulePracticeActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_module_id',
        'title',
        'type',
        'description',
    ];

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }
}
