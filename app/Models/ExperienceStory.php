<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fullText(): string
    {
        return trim(implode(' ', array_filter([$this->situation, $this->task, $this->action, $this->result])));
    }
}
