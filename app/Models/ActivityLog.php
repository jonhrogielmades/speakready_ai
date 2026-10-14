<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Activities that are useful in the admin notification feed.
     *
     * Routine user interactions remain available in the user's own activity
     * history, but should not create noise in the admin feed.
     */
    public const ADMIN_IMPORTANT_ACTIONS = [
        'user_logged_in',
        'user_logged_out',
        'password_changed',
        'account_deleted',
        'interview_completed',
        'interview_session_deleted',
        'interview_sessions_cleared',
        'learning_game_completed',
        'learning_game_certificate_issued',
        'perk_unlocked',
        'job_application_created',
        'job_application_deleted',
        'ai_coach_conversation_deleted',
        'ai_coach_conversations_cleared',
        'admin_profile_updated',
        'admin_password_changed',
        'admin_settings_updated',
        'admin_settings_backup_downloaded',
        'admin_settings_backup_restored',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function scopeImportantForAdmin(Builder $query): Builder
    {
        return $query->whereIn('action', self::ADMIN_IMPORTANT_ACTIONS);
    }
}
