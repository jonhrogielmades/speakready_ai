<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscription extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected $fillable = [
        'email',
        'status',
        'source',
        'confirmation_token_hash',
        'confirmation_expires_at',
        'confirmed_at',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'confirmation_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];
}
