<?php

namespace Tests\Feature;

use App\Support\VoiceSessionSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VoiceSessionSchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_voice_session_schema_repair_adds_missing_columns(): void
    {
        Schema::dropIfExists('voice_sessions');

        Schema::create('voice_sessions', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        VoiceSessionSchema::ensure(force: true, createIfMissing: true);

        foreach ([
            'user_id',
            'speaking_pace',
            'clarity_score',
            'confidence_score',
            'filler_words',
            'category',
            'prompt',
            'transcript',
            'ai_feedback_strengths',
            'ai_feedback_weaknesses',
            'ai_improved_answer',
            'duration_seconds',
            'wpm',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('voice_sessions', $column),
                "Expected voice_sessions.{$column} to exist after schema repair."
            );
        }
    }
}
