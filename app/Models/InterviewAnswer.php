<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewAnswer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'star_analysis' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function interviewSession()
    {
        return $this->belongsTo(InterviewSession::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(FeedbackAuditLog::class);
    }

    public function complaints()
    {
        return $this->hasMany(FeedbackComplaint::class);
    }

    public function scopeFlagged($query)
    {
        return $query->where('audit_status', 'flagged');
    }

    public function scopePending($query)
    {
        return $query->where('audit_status', 'under_review');
    }
}
