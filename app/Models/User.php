<?php

namespace App\Models;

use App\Services\BrevoTransactionalMail;
use Illuminate\Auth\Notifications\ResetPassword;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
        'is_admin',
        'google_id',
        'status',
        'reactivation_requested_at',
        'profile_photo_path',
        'target_position',
        'preferred_language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function interviews()
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
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

    public static function normalizeUsername(?string $username): string
    {
        $username = Str::lower(Str::ascii(trim((string) $username)));
        $username = preg_replace('/[^a-z0-9_]+/', '_', $username) ?? '';

        return trim($username, '_');
    }

    public static function generateUniqueUsernameFrom(?string $source, ?int $ignoreUserId = null): string
    {
        $source = trim((string) $source);
        $baseSource = Str::contains($source, '@') ? Str::before($source, '@') : $source;
        $base = self::normalizeUsername($baseSource) ?: 'user';
        $base = trim(Str::limit($base, 24, ''), '_') ?: 'user';
        $candidate = $base;
        $suffix = 2;

        while (self::usernameExists($candidate, $ignoreUserId)) {
            $suffixText = (string) $suffix;
            $prefixLength = max(1, 30 - strlen($suffixText));
            $prefix = trim(Str::limit($base, $prefixLength, ''), '_') ?: 'user';
            $candidate = $prefix . $suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private static function usernameExists(string $username, ?int $ignoreUserId = null): bool
    {
        return self::withTrashed()
            ->whereNotNull('username')
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->whereRaw('LOWER(username) = ?', [Str::lower($username)])
            ->exists();
    }
}
