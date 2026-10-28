<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOnboardingProgress extends Model
{
    use HasFactory;

    protected $table = 'user_onboarding_progress';

    protected $fillable = [
        'user_id',
        'recovery_code_ack_required_at',
        'recovery_code_saved_at',
        'profile_personalized_at',
        'personalization_skipped_at',
        'setup_tools_completed_at',
        'setup_tools_snoozed_at',
        'dashboard_tour_completed_at',
        'dashboard_tour_skipped_at',
        'first_interview_completed_at',
        'activated_at',
        'experience_level',
        'interview_goal',
        'interview_date',
        'setup_tools',
    ];

    protected $casts = [
        'recovery_code_ack_required_at' => 'datetime',
        'recovery_code_saved_at' => 'datetime',
        'profile_personalized_at' => 'datetime',
        'personalization_skipped_at' => 'datetime',
        'setup_tools_completed_at' => 'datetime',
        'setup_tools_snoozed_at' => 'datetime',
        'dashboard_tour_completed_at' => 'datetime',
        'dashboard_tour_skipped_at' => 'datetime',
        'first_interview_completed_at' => 'datetime',
        'activated_at' => 'datetime',
        'interview_date' => 'date',
        'setup_tools' => 'array',
    ];

    public static function forUser(User|int $user): static
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        return static::query()->firstOrCreate(['user_id' => $userId]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requiresRecoveryCodeAcknowledgement(?User $user = null): bool
    {
        $user ??= $this->user;

        if ($user?->isGoogleAccount()) {
            return false;
        }

        return (bool) $this->recovery_code_ack_required_at && ! $this->recovery_code_saved_at;
    }

    /** Reconcile persisted onboarding state with authoritative account activity. */
    public function syncFromUserState(User $user): static
    {
        if (! $this->first_interview_completed_at) {
            $firstScoredInterview = InterviewSession::query()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereHas('score')
                ->oldest('updated_at')
                ->first();

            if ($firstScoredInterview) {
                $this->first_interview_completed_at = $firstScoredInterview->updated_at
                    ?? $firstScoredInterview->created_at
                    ?? now();
            }
        }

        if (! $this->activated_at && $user->hasAcceptedCurrentTerms() && $this->first_interview_completed_at) {
            $this->activated_at = now();
        }

        if ($this->isDirty()) {
            $this->save();
        }

        return $this;
    }

    public function toStateArray(User $user): array
    {
        $this->syncFromUserState($user);

        $recoveryCodeSaved = $user->isGoogleAccount() || (bool) $this->recovery_code_saved_at;
        $profileHandled = (bool) $this->profile_personalized_at || (bool) $this->personalization_skipped_at;
        $emailVerified = $user->isGoogleAccount() || $user->hasVerifiedEmail();
        $firstStepsComplete = $recoveryCodeSaved
            && $profileHandled
            && (bool) $this->setup_tools_completed_at
            && $emailVerified
            && (bool) $this->dashboard_tour_completed_at;

        return [
            'recovery_code_ack_required' => $this->requiresRecoveryCodeAcknowledgement($user),
            'recovery_code_applicable' => $user->isLocalAccount(),
            'recovery_code_saved' => $recoveryCodeSaved,
            'profile_personalized' => (bool) $this->profile_personalized_at,
            'personalization_skipped' => (bool) $this->personalization_skipped_at,
            'setup_tools_completed' => (bool) $this->setup_tools_completed_at,
            'setup_tools_snoozed' => (bool) $this->setup_tools_snoozed_at,
            'email_verification_applicable' => $user->isLocalAccount(),
            'email_verified' => $emailVerified,
            'dashboard_tour_completed' => (bool) $this->dashboard_tour_completed_at,
            'dashboard_tour_skipped' => (bool) $this->dashboard_tour_skipped_at,
            'first_steps_complete' => $firstStepsComplete,
            'first_interview_completed' => (bool) $this->first_interview_completed_at,
            'terms_accepted' => $user->hasAcceptedCurrentTerms(),
            'activated' => $user->hasAcceptedCurrentTerms() && (bool) $this->first_interview_completed_at,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'experience_level' => $this->experience_level,
            'interview_goal' => $this->interview_goal,
            'interview_date' => $this->interview_date?->toDateString(),
            'setup_tools' => $this->setup_tools ?? [],
        ];
    }
}
