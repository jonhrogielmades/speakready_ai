<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('game_sessions')) {
            Schema::create('game_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('game_level_id')->constrained('game_levels')->cascadeOnDelete();
                $table->string('status')->default('in_progress');
                $table->string('difficulty')->nullable();
                $table->string('target_position')->nullable();
                $table->integer('num_questions')->default(0);
                $table->string('response_mode')->default('hybrid');
                $table->text('interview_focus')->nullable();
                $table->string('company_persona')->nullable();
                $table->integer('time_limit')->default(0);
                $table->json('questions')->nullable();
                $table->json('accommodation_profile')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->text('notes')->nullable();
                $table->integer('current_question_index')->default(0);
                $table->json('session_state')->nullable();
                $table->integer('score')->nullable();
                $table->integer('required_score')->nullable();
                $table->string('result_status')->nullable();
                $table->json('goal_breakdown')->nullable();
                $table->integer('xp_earned')->default(0);
                $table->integer('energy_spent')->default(0);
                $table->integer('energy_remaining')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'game_level_id', 'status']);
            });
        }

        if (! Schema::hasTable('game_answers')) {
            Schema::create('game_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
                $table->unsignedInteger('question_index');
                $table->text('question_text');
                $table->text('answer_text')->nullable();
                $table->boolean('is_skipped')->default(false);
                $table->string('response_mode')->default('text');
                $table->integer('elapsed_seconds')->default(0);
                $table->integer('wpm')->default(0);
                $table->integer('voice_duration')->default(0);
                $table->integer('filler_words_count')->default(0);
                $table->integer('pause_count')->default(0);
                $table->integer('confidence_score')->default(0);
                $table->integer('eye_contact_score')->default(0);
                $table->integer('posture_score')->default(0);
                $table->integer('goal_score')->nullable();
                $table->integer('clarity_score')->nullable();
                $table->integer('relevance_score')->nullable();
                $table->integer('grammar_score')->nullable();
                $table->integer('professionalism_score')->nullable();
                $table->integer('star_method_score')->nullable();
                $table->json('goal_breakdown')->nullable();
                $table->text('goal_notes')->nullable();
                $table->timestamps();

                $table->unique(['game_session_id', 'question_index']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_answers');
        Schema::dropIfExists('game_sessions');
    }
};
