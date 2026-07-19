<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Feedback extends Model
{
    protected $fillable = [
        'interview_session_id',
        'strengths',
        'weaknesses',
        'improvement_suggestions',
        'coaching_summary',
    ];

    protected $casts = [
        'coaching_summary' => 'array',
    ];

    public static function hasColumn(string $column): bool
    {
        $columns = Schema::hasTable('feedback')
            ? array_flip(Schema::getColumnListing('feedback'))
            : [];

        return isset($columns[$column]);
    }

    public function setAttribute($key, $value)
    {
        if ($this->isFillable($key) && ! self::hasColumn($key)) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
