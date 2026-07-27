<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('voice_sessions')) {
            return;
        }

        Schema::table('voice_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('voice_sessions', 'category')) {
                $table->string('category')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'prompt')) {
                $table->text('prompt')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'transcript')) {
                $table->text('transcript')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'ai_feedback_strengths')) {
                $table->text('ai_feedback_strengths')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'ai_feedback_weaknesses')) {
                $table->text('ai_feedback_weaknesses')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'ai_improved_answer')) {
                $table->text('ai_improved_answer')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'duration_seconds')) {
                $table->integer('duration_seconds')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'wpm')) {
                $table->integer('wpm')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('voice_sessions')) {
            return;
        }

        Schema::table('voice_sessions', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('voice_sessions', 'category') ? 'category' : null,
                Schema::hasColumn('voice_sessions', 'prompt') ? 'prompt' : null,
                Schema::hasColumn('voice_sessions', 'transcript') ? 'transcript' : null,
                Schema::hasColumn('voice_sessions', 'ai_feedback_strengths') ? 'ai_feedback_strengths' : null,
                Schema::hasColumn('voice_sessions', 'ai_feedback_weaknesses') ? 'ai_feedback_weaknesses' : null,
                Schema::hasColumn('voice_sessions', 'ai_improved_answer') ? 'ai_improved_answer' : null,
                Schema::hasColumn('voice_sessions', 'duration_seconds') ? 'duration_seconds' : null,
                Schema::hasColumn('voice_sessions', 'wpm') ? 'wpm' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
