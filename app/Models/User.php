<?php

namespace App\Models;

use App\Services\BrevoTransactionalMail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    public const TERMS_VERSION = '2026-09-23';

    public const AUTH_METHOD_LOCAL = 'local';

    public const AUTH_METHOD_GOOGLE = 'google';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'password_recovery_code_hash',
        'password_recovery_code_generated_at',
        'password_recovery_code_expires_at',
        'is_admin',
        'google_id',
        'auth_method',
        'status',
        'reactivation_requested_at',
        'profile_photo_path',
        'target_position',
        'preferred_language',
        'terms_accepted_at',
        'terms_version',
        'terms_ip_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_recovery_code_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'password' => 'hashed',
        'password_recovery_code_generated_at' => 'datetime',
        'password_recovery_code_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->auth_method)) {
                $user->auth_method = filled($user->google_id)
                    ? self::AUTH_METHOD_GOOGLE
                    : self::AUTH_METHOD_LOCAL;
            }
        });
    }

    public function isLocalAccount(): bool
    {
        return $this->auth_method === self::AUTH_METHOD_LOCAL;
    }

    public function isGoogleAccount(): bool
    {
        return $this->auth_method === self::AUTH_METHOD_GOOGLE;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profilePhotoUrl();
    }

    public function profilePhotoUrl(): ?string
    {
        $path = trim((string) $this->profile_photo_path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, '//')) {
            return 'https:'.$path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return asset(ltrim($path, '/'));
        }

        if (Str::startsWith($path, '/')) {
            return url($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    public function hasAcceptedCurrentTerms(): bool
    {
        return $this->terms_accepted_at !== null
            && $this->terms_version === self::TERMS_VERSION;
    }

    public function interviews()
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function onboardingProgress()
    {
        return $this->hasOne(UserOnboardingProgress::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function missionAttempts()
    {
        return $this->hasMany(MissionAttempt::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $brevoMailer = app(BrevoTransactionalMail::class);

        if ($brevoMailer->isConfigured()) {
            $brevoMailer->sendPasswordReset($this, $token);

            return;
        }

        $this->notify(new ResetPassword($token));
    }
}
