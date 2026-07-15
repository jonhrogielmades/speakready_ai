<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ReadinessProfile extends Model
{
    protected $fillable = [
        'user_id', 'job_application_id', 'target_role', 'competency_map', 'mastery_snapshot',
        'future_skills', 'next_actions', 'version', 'calibrated_at',
    ];

    protected $casts = [
        'competency_map' => 'array',
        'mastery_snapshot' => 'array',
        'future_skills' => 'array',
        'next_actions' => 'array',
        'version' => 'integer',
        'calibrated_at' => 'datetime',
    ];

    public static function tableExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('readiness_profiles');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }
}
