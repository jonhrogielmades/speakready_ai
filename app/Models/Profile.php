<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Profile extends Model
{
    use HasFactory;

    public const MAX_ENERGY = 20;

    protected $fillable = [
        'user_id',
        'total_sessions',
        'readiness_score',
        'profile_picture',
        'personal_information',
        'inclusive_preferences',
        'experience_points',
        'player_level',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'energy',
        'energy_last_refilled_at',
        'badges_earned',
        'leadership_xp',
        'communication_xp',
        'technical_xp',
        'problem_solving_xp',
        'unlocked_perks',
    ];

    protected $casts = [
        'total_sessions' => 'integer',
        'readiness_score' => 'integer',
        'experience_points' => 'integer',
        'player_level' => 'integer',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_activity_date' => 'date',
        'energy' => 'integer',
        'energy_last_refilled_at' => 'datetime',
        'leadership_xp' => 'integer',
        'communication_xp' => 'integer',
        'technical_xp' => 'integer',
        'problem_solving_xp' => 'integer',
        'unlocked_perks' => 'array',
        'badges_earned' => 'array',
        'inclusive_preferences' => 'array',
    ];

    public static function hasColumn(string $column): bool
    {
        static $columns = null;

        $columns ??= Schema::hasTable('profiles')
            ? array_flip(Schema::getColumnListing('profiles'))
            : [];

        return isset($columns[$column]);
    }

    public function setAttribute($key, $value)
    {
        if ($this->isFillable($key) && ! self::hasColumn($key)) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasPerk($perk)
    {
        $perks = $this->unlocked_perks;

        if (is_string($perks)) {
            $perks = json_decode($perks, true) ?: [];
        }

        if (empty($perks) || !is_array($perks)) {
            return false;
        }

        return in_array($perk, $perks, true);
    }
}
