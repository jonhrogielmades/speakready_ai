<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProviderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'module',
        'endpoint',
        'response_time_ms',
        'tokens_used',
        'cost',
        'status',
        'error_message',
    ];
}
