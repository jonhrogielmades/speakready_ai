<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_sessions', 'interviewer_strictness')) {
                $table->string('interviewer_strictness')->default('neutral')->after('company_persona');
            }

            if (!Schema::hasColumn('interview_sessions', 'live_feedback_mode')) {
                $table->string('live_feedback_mode')->default('coaching')->after('ai_assistance_level');
            }

            if (!Schema::hasColumn('interview_sessions', 'current_question_index')) {
                $table->integer('current_question_index')->default(0)->after('duration_seconds');
            }

            if (!Schema::hasColumn('interview_sessions', 'session_state')) {
                $table->longText('session_state')->nullable()->after('current_question_index');
            }

            if (!Schema::hasColumn('interview_sessions', 'action_plan')) {
                $table->json('action_plan')->nullable()->after('session_state');
            }
        });

        Schema::table('interview_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_answers', 'retry_of_answer_id')) {
                $table->foreignId('retry_of_answer_id')
                    ->nullable()
                    ->after('interview_session_id')
                    ->constrained('interview_answers')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('interview_answers', 'attempt_number')) {
                $table->integer('attempt_number')->default(1)->after('retry_of_answer_id');
            }

            if (!Schema::hasColumn('interview_answers', 'transcript_timeline')) {
                $table->json('transcript_timeline')->nullable()->after('answer_text');
            }

            if (!Schema::hasColumn('interview_answers', 'timed_out')) {
                $table->boolean('timed_out')->default(false)->after('is_skipped');
            }

            if (!Schema::hasColumn('interview_answers', 'elapsed_seconds')) {
                $table->integer('elapsed_seconds')->default(0)->after('timed_out');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interview_answers', function (Blueprint $table) {
            if (Schema::hasColumn('interview_answers', 'retry_of_answer_id')) {
                $table->dropConstrainedForeignId('retry_of_answer_id');
            }

            $dropColumns = array_filter([
                Schema::hasColumn('interview_answers', 'attempt_number') ? 'attempt_number' : null,
                Schema::hasColumn('interview_answers', 'transcript_timeline') ? 'transcript_timeline' : null,
                Schema::hasColumn('interview_answers', 'timed_out') ? 'timed_out' : null,
                Schema::hasColumn('interview_answers', 'elapsed_seconds') ? 'elapsed_seconds' : null,
            ]);

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('interview_sessions', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('interview_sessions', 'interviewer_strictness') ? 'interviewer_strictness' : null,
                Schema::hasColumn('interview_sessions', 'live_feedback_mode') ? 'live_feedback_mode' : null,
                Schema::hasColumn('interview_sessions', 'current_question_index') ? 'current_question_index' : null,
                Schema::hasColumn('interview_sessions', 'session_state') ? 'session_state' : null,
                Schema::hasColumn('interview_sessions', 'action_plan') ? 'action_plan' : null,
            ]);

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
