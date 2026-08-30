<?php

namespace Tests\Feature;

use App\Models\User;
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
            'pronunciation_analysis',
            'pronunciation_score',
            'duration_seconds',
            'wpm',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('voice_sessions', $column),
                "Expected voice_sessions.{$column} to exist after schema repair."
            );
        }
    }

    public function test_voice_rehearsal_page_repairs_missing_voice_session_table(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        Schema::dropIfExists('voice_sessions');

        $this->actingAs($user)
            ->get(route('user.drills.voice'))
            ->assertOk()
            ->assertSee('Philippines Voice Rehearsal');

        $this->assertTrue(Schema::hasTable('voice_sessions'));
        $this->assertTrue(Schema::hasColumn('voice_sessions', 'pronunciation_score'));
    }

    public function test_voice_session_save_repairs_missing_voice_session_table(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        Schema::dropIfExists('voice_sessions');

        $this->actingAs($user)
            ->postJson(route('user.drills.voice.save'), [
                'category' => 'Tell Me About Yourself',
                'prompt' => 'Walk me through your background.',
                'transcript' => 'I handled a customer issue by listening carefully, coordinating with my team, and sharing a clear next step.',
                'duration_seconds' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('voice_sessions', [
            'user_id' => $user->id,
            'category' => 'Tell Me About Yourself',
        ]);
    }
}
