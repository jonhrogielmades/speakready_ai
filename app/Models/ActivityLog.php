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
        'user_registered',
        'user_logged_in',
        'user_logged_out',
        'reactivation_requested',
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
        'admin_password_recovery_code_rotated',
        'admin_settings_updated',
        'admin_settings_backup_downloaded',
        'admin_settings_backup_restored',
        'admin_user_created',
        'admin_user_updated',
        'admin_user_reactivation_approved',
        'admin_user_deleted',
        'admin_user_restored',
    ];

    /**
     * User changes worth surfacing on the dashboard without turning every
     * routine interaction into an important admin notification.
     */
    public const ADMIN_DASHBOARD_USER_ACTIONS = [
        'user_registered',
        'reactivation_requested',
        'profile_updated',
        'password_changed',
        'language_updated',
        'account_deleted',
        'interview_started',
        'interview_completed',
        'interview_session_deleted',
        'interview_sessions_cleared',
        'learning_game_started',
        'learning_game_completed',
        'learning_game_certificate_issued',
        'learning_game_certificate_downloaded',
        'learning_module_progress_updated',
        'perk_unlocked',
        'job_application_created',
        'job_application_updated',
        'job_application_deleted',
        'ai_coach_conversation_started',
        'ai_coach_conversation_deleted',
        'ai_coach_conversations_cleared',
    ];

    /**
     * Activities that deserve a place in the user's notifications feed.
     *
     * Routine interactions such as starting a session, sending a coach
     * message, or saving incremental learning progress remain in the audit
     * log, but do not create noise in the user's important activity history.
     */
    public const USER_IMPORTANT_ACTIONS = [
        'user_registered',
        'user_logged_in',
        'user_logged_out',
        'reactivation_requested',
        'profile_updated',
        'password_changed',
        'language_updated',
        'account_deleted',
        'interview_completed',
        'interview_session_deleted',
        'interview_sessions_cleared',
        'learning_game_completed',
        'learning_game_certificate_issued',
        'learning_game_certificate_downloaded',
        'perk_unlocked',
        'job_application_created',
        'job_application_updated',
        'job_application_deleted',
        'ai_coach_conversation_deleted',
        'ai_coach_conversations_cleared',
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

    public function scopeDashboardUserUpdates(Builder $query): Builder
    {
        return $query
            ->whereIn('action', self::ADMIN_DASHBOARD_USER_ACTIONS)
            ->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->where('is_admin', false));
    }
}
