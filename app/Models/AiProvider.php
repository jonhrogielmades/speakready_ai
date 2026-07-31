<?php

namespace App\Models;

use App\Support\AiProviderSchema;
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

    public static function safePrimaryOrActive(): ?self
    {
        try {
            AiProviderSchema::ensure();

            return self::where('is_primary', true)->first()
                ?? self::where('status', 'active')->first();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function safeActiveProviderName(): ?string
    {
        return self::safePrimaryOrActive()?->name;
    }

    public static function safeActiveOpenAiConfigured(): bool
    {
        try {
            AiProviderSchema::ensure();

            return self::where('name', 'like', '%OpenAI%')
                ->where('status', 'active')
                ->whereNotNull('api_key')
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
