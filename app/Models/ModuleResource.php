<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_module_id',
        'title',
        'file_path',
        'file_type',
    ];

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }
}
