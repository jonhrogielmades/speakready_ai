<?php

namespace App\Support;

use App\Models\InterviewAnswer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InterviewAnswerSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredColumns()) {
            return;
        }

        if (! Schema::hasTable('interview_answers')) {
            if ($createIfMissing && Schema::hasTable('interview_sessions') && Schema::hasTable('questions')) {
                self::createTable();
            }

            self::$checked = true;
            self::flushModelColumnCache();

            return;
        }

        $missing = self::missingColumns(self::requiredColumns());
        if ($missing !== []) {
            Schema::table('interview_answers', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'interview_session_id')) {
                    self::foreignId($table, 'interview_session_id', 'interview_sessions');
                }
                if (self::isMissing($missing, 'retry_of_answer_id')) {
                    $table->unsignedBigInteger('retry_of_answer_id')->nullable();
                }
                if (self::isMissing($missing, 'attempt_number')) {
                    $table->integer('attempt_number')->default(1);
                }
                if (self::isMissing($missing, 'question_id')) {
                    self::foreignId($table, 'question_id', 'questions', true);
                }
                if (self::isMissing($missing, 'answer_text')) {
                    $table->text('answer_text')->nullable();
                }
                if (self::isMissing($missing, 'delivery_transcript')) {
                    $table->text('delivery_transcript')->nullable();
                }
                if (self::isMissing($missing, 'transcript_timeline')) {
                    $table->json('transcript_timeline')->nullable();
                }
                if (self::isMissing($missing, 'paste_event_count')) {
                    $table->unsignedSmallInteger('paste_event_count')->default(0);
                }
                if (self::isMissing($missing, 'pasted_character_count')) {
                    $table->unsignedInteger('pasted_character_count')->default(0);
                }
                if (self::isMissing($missing, 'ai_generated_likelihood')) {
                    $table->unsignedTinyInteger('ai_generated_likelihood')->nullable();
                }
                if (self::isMissing($missing, 'answer_integrity_flags')) {
                    $table->json('answer_integrity_flags')->nullable();
                }
                if (self::isMissing($missing, 'observation_data')) {
                    $table->json('observation_data')->nullable();
                }
                if (self::isMissing($missing, 'pronunciation_analysis')) {
                    $table->json('pronunciation_analysis')->nullable();
                }
                if (self::isMissing($missing, 'pronunciation_score')) {
                    $table->unsignedTinyInteger('pronunciation_score')->nullable();
                }
                if (self::isMissing($missing, 'coaching_feedback')) {
                    $table->json('coaching_feedback')->nullable();
                }
                if (self::isMissing($missing, 'response_mode')) {
                    $table->string('response_mode')->default('text');
                }
                if (self::isMissing($missing, 'ai_feedback')) {
                    $table->text('ai_feedback')->nullable();
                }
                if (self::isMissing($missing, 'better_sample_answer')) {
                    $table->text('better_sample_answer')->nullable();
                }
                if (self::isMissing($missing, 'follow_up_question')) {
                    $table->text('follow_up_question')->nullable();
                }
                if (self::isMissing($missing, 'score')) {
                    $table->integer('score')->nullable();
                }
                if (self::isMissing($missing, 'is_skipped')) {
                    $table->boolean('is_skipped')->default(false);
                }
                if (self::isMissing($missing, 'timed_out')) {
                    $table->boolean('timed_out')->default(false);
                }
                if (self::isMissing($missing, 'elapsed_seconds')) {
                    $table->integer('elapsed_seconds')->default(0);
                }
                if (self::isMissing($missing, 'voice_duration')) {
                    $table->integer('voice_duration')->default(0);
                }
                if (self::isMissing($missing, 'wpm')) {
                    $table->integer('wpm')->default(0);
                }
                if (self::isMissing($missing, 'filler_words_count')) {
                    $table->integer('filler_words_count')->default(0);
                }
                if (self::isMissing($missing, 'pause_count')) {
                    $table->integer('pause_count')->default(0);
                }
                if (self::isMissing($missing, 'eye_contact_score')) {
                    $table->integer('eye_contact_score')->default(0);
                }
                if (self::isMissing($missing, 'posture_score')) {
                    $table->integer('posture_score')->default(0);
                }
                if (self::isMissing($missing, 'clarity_score')) {
                    $table->integer('clarity_score')->nullable();
                }
                if (self::isMissing($missing, 'relevance_score')) {
                    $table->integer('relevance_score')->nullable();
                }
                if (self::isMissing($missing, 'confidence_score')) {
                    $table->integer('confidence_score')->nullable();
                }
                if (self::isMissing($missing, 'delivery_stability_score')) {
                    $table->unsignedTinyInteger('delivery_stability_score')->nullable();
                }
                if (self::isMissing($missing, 'self_reported_confidence')) {
                    $table->unsignedTinyInteger('self_reported_confidence')->nullable();
                }
                if (self::isMissing($missing, 'scoring_confidence')) {
                    $table->unsignedTinyInteger('scoring_confidence')->nullable();
                }
                if (self::isMissing($missing, 'grammar_score')) {
                    $table->integer('grammar_score')->nullable();
                }
                if (self::isMissing($missing, 'audit_status')) {
                    $table->string('audit_status')->default('under_review');
                }
                if (self::isMissing($missing, 'flagged_reason')) {
                    $table->string('flagged_reason')->nullable();
                }
                if (self::isMissing($missing, 'star_analysis')) {
                    $table->json('star_analysis')->nullable();
                }
                if (self::isMissing($missing, 'recommendation_text')) {
                    $table->text('recommendation_text')->nullable();
                }
                if (self::isMissing($missing, 'evidence_map')) {
                    $table->json('evidence_map')->nullable();
                }
                if (self::isMissing($missing, 'rubric_level')) {
                    $table->string('rubric_level')->nullable();
                }
                if (self::isMissing($missing, 'improved_answer_source')) {
                    $table->string('improved_answer_source')->default('illustrative');
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        self::$checked = true;
        self::flushModelColumnCache();
    }

    public static function hasRequiredColumns(): bool
    {
        return Schema::hasTable('interview_answers')
            && self::missingColumns(self::requiredColumns()) === [];
    }

    private static function createTable(): void
    {
        Schema::create('interview_answers', function (Blueprint $table): void {
            $table->id();
            self::foreignId($table, 'interview_session_id', 'interview_sessions');
            $table->unsignedBigInteger('retry_of_answer_id')->nullable();
            $table->integer('attempt_number')->default(1);
            self::foreignId($table, 'question_id', 'questions', true);
            $table->text('answer_text')->nullable();
            $table->text('delivery_transcript')->nullable();
            $table->json('transcript_timeline')->nullable();
            $table->unsignedSmallInteger('paste_event_count')->default(0);
            $table->unsignedInteger('pasted_character_count')->default(0);
            $table->unsignedTinyInteger('ai_generated_likelihood')->nullable();
            $table->json('answer_integrity_flags')->nullable();
            $table->json('observation_data')->nullable();
            $table->json('pronunciation_analysis')->nullable();
            $table->unsignedTinyInteger('pronunciation_score')->nullable();
            $table->json('coaching_feedback')->nullable();
            $table->string('response_mode')->default('text');
            $table->text('ai_feedback')->nullable();
            $table->text('better_sample_answer')->nullable();
            $table->text('follow_up_question')->nullable();
            $table->integer('score')->nullable();
            $table->boolean('is_skipped')->default(false);
            $table->boolean('timed_out')->default(false);
            $table->integer('elapsed_seconds')->default(0);
            $table->integer('voice_duration')->default(0);
            $table->integer('wpm')->default(0);
            $table->integer('filler_words_count')->default(0);
            $table->integer('pause_count')->default(0);
            $table->integer('eye_contact_score')->default(0);
            $table->integer('posture_score')->default(0);
            $table->integer('clarity_score')->nullable();
            $table->integer('relevance_score')->nullable();
            $table->integer('confidence_score')->nullable();
            $table->unsignedTinyInteger('delivery_stability_score')->nullable();
            $table->unsignedTinyInteger('self_reported_confidence')->nullable();
            $table->unsignedTinyInteger('scoring_confidence')->nullable();
            $table->integer('grammar_score')->nullable();
            $table->string('audit_status')->default('under_review');
            $table->string('flagged_reason')->nullable();
            $table->json('star_analysis')->nullable();
            $table->text('recommendation_text')->nullable();
            $table->json('evidence_map')->nullable();
            $table->string('rubric_level')->nullable();
            $table->string('improved_answer_source')->default('illustrative');
            $table->timestamps();
        });
    }

    private static function foreignId(Blueprint $table, string $column, string $relatedTable, bool $nullable = false): void
    {
        if (Schema::hasTable($relatedTable)) {
            $definition = $table->foreignId($column);

            if ($nullable) {
                $definition->nullable();
            }

            $definition->constrained($relatedTable);
            $nullable ? $definition->nullOnDelete() : $definition->cascadeOnDelete();

            return;
        }

        $definition = $table->unsignedBigInteger($column);

        if ($nullable) {
            $definition->nullable();
        }
    }

    private static function requiredColumns(): array
    {
        return [
            'interview_session_id',
            'retry_of_answer_id',
            'attempt_number',
            'question_id',
            'answer_text',
            'delivery_transcript',
            'transcript_timeline',
            'paste_event_count',
            'pasted_character_count',
            'ai_generated_likelihood',
            'answer_integrity_flags',
            'observation_data',
            'pronunciation_analysis',
            'pronunciation_score',
            'coaching_feedback',
            'response_mode',
            'ai_feedback',
            'better_sample_answer',
            'follow_up_question',
            'score',
            'is_skipped',
            'timed_out',
            'elapsed_seconds',
            'voice_duration',
            'wpm',
            'filler_words_count',
            'pause_count',
            'eye_contact_score',
            'posture_score',
            'clarity_score',
            'relevance_score',
            'confidence_score',
            'delivery_stability_score',
            'self_reported_confidence',
            'scoring_confidence',
            'grammar_score',
            'audit_status',
            'flagged_reason',
            'star_analysis',
            'recommendation_text',
            'evidence_map',
            'rubric_level',
            'improved_answer_source',
            'created_at',
            'updated_at',
        ];
    }

    private static function missingColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn('interview_answers', $column)
        ));
    }

    private static function isMissing(array $missing, string $column): bool
    {
        return in_array($column, $missing, true);
    }

    private static function flushModelColumnCache(): void
    {
        if (method_exists(InterviewAnswer::class, 'flushColumnCache')) {
            InterviewAnswer::flushColumnCache();
        }
    }
}
