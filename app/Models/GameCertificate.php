<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'final_game_level_id',
        'certificate_code',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function finalLevel()
    {
        return $this->belongsTo(GameLevel::class, 'final_game_level_id');
    }
}
