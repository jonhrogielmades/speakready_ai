<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameProgress extends Model
{
    use HasFactory;

    protected $table = 'game_progress';

    protected $fillable = [
        'user_id',
        'game_level_id',
        'status',
        'best_score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(GameLevel::class, 'game_level_id');
    }
}
