<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    public const INTERVIEW_SETUP_CACHE_KEY = 'interview_setup.categories.job.v1';

    protected $fillable = ['title', 'type', 'description', 'icon', 'status', 'is_featured', 'sort_order'];

    protected static function booted(): void
    {
        static::saved(static fn (): bool => Cache::forget(self::INTERVIEW_SETUP_CACHE_KEY));
        static::deleted(static fn (): bool => Cache::forget(self::INTERVIEW_SETUP_CACHE_KEY));
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

}
