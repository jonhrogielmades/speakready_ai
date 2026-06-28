<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArenaProgress extends Model
{
    use HasFactory;

    protected $table = 'arena_progress';

    protected $fillable = [
        'user_id',
        'arena_level_id',
        'status',
        'best_score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(ArenaLevel::class, 'arena_level_id');
    }
}
