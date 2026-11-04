<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModulePracticeSubmission extends Model
{
    protected $fillable = ['user_id', 'module_practice_activity_id', 'response_text'];

    public function activity()
    {
        return $this->belongsTo(ModulePracticeActivity::class, 'module_practice_activity_id');
    }
}
