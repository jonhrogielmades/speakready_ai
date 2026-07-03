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
        Schema::table('voice_sessions', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->text('prompt')->nullable();
            $table->text('transcript')->nullable();
            $table->text('ai_feedback_strengths')->nullable();
            $table->text('ai_feedback_weaknesses')->nullable();
            $table->text('ai_improved_answer')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('wpm')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voice_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'prompt',
                'transcript',
                'ai_feedback_strengths',
                'ai_feedback_weaknesses',
                'ai_improved_answer',
                'duration_seconds',
                'wpm'
            ]);
        });
    }
};
