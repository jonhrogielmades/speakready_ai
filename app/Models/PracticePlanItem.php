<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticePlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_application_id',
        'interview_session_id',
        'day_number',
        'due_date',
        'type',
        'title',
        'task',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'due_date' => 'date',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function interviewSession()
    {
        return $this->belongsTo(InterviewSession::class);
    }
}
