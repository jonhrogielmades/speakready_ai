<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_endpoint',
        'api_key',
        'status',
        'is_primary',
        'is_fallback',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_fallback' => 'boolean',
    ];
}
