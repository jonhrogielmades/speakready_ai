<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ExperienceStory extends Model
{
    protected $fillable = [
        'user_id', 'title', 'context_type', 'situation', 'task', 'action', 'result',
        'verified_facts', 'metrics', 'competency_tags', 'facts_confirmed', 'visibility',
    ];

    protected $casts = [
        'verified_facts' => 'array',
        'metrics' => 'array',
        'competency_tags' => 'array',
        'facts_confirmed' => 'boolean',
    ];

    public static function tableExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('experience_stories');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (! self::tableExists()) {
            abort(404);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fullText(): string
    {
        return trim(implode(' ', array_filter([$this->situation, $this->task, $this->action, $this->result])));
    }
}
