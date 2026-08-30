<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GameSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false): void
    {
        if (! $force && self::$checked && self::hasRequiredTables()) {
            return;
        }

        self::ensureGameProgressTable();
        self::ensureGameSessionsTable();
        self::ensureGameAnswersTable();
        self::ensureGameCertificatesTable();

        self::$checked = true;
    }

    public static function hasRequiredTables(): bool
    {
        return Schema::hasTable('game_progress')
            && Schema::hasTable('game_sessions')
            && Schema::hasTable('game_answers')
            && Schema::hasTable('game_certificates');
    }

    private static function ensureGameProgressTable(): void
    {
        if (! Schema::hasTable('game_progress')) {
            Schema::create('game_progress', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users');
                self::foreignId($table, 'game_level_id', 'game_levels');
                $table->string('status')->default('locked');
                $table->integer('best_score')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'game_level_id']);
            });

            return;
        }

        $missing = self::missingColumns('game_progress', [
            'user_id',
            'game_level_id',
            'status',
            'best_score',
            'created_at',
            'updated_at',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('game_progress', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'user_id')) {
                self::foreignId($table, 'user_id', 'users', true);
            }
            if (self::isMissing($missing, 'game_level_id')) {
                self::foreignId($table, 'game_level_id', 'game_levels', true);
            }
            if (self::isMissing($missing, 'status')) {
                $table->string('status')->default('locked');
            }
            if (self::isMissing($missing, 'best_score')) {
                $table->integer('best_score')->default(0);
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureGameSessionsTable(): void
    {
        if (! Schema::hasTable('game_sessions')) {
            Schema::create('game_sessions', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users');
                self::foreignId($table, 'game_level_id', 'game_levels');
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

            return;
        }

        $missing = self::missingColumns('game_sessions', [
            'user_id',
            'game_level_id',
            'status',
            'difficulty',
            'target_position',
            'num_questions',
            'response_mode',
            'interview_focus',
            'company_persona',
            'time_limit',
            'questions',
            'accommodation_profile',
            'duration_seconds',
            'notes',
            'current_question_index',
            'session_state',
            'score',
            'required_score',
            'result_status',
            'goal_breakdown',
            'xp_earned',
            'energy_spent',
            'energy_remaining',
            'started_at',
            'completed_at',
            'created_at',
            'updated_at',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('game_sessions', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'user_id')) {
                self::foreignId($table, 'user_id', 'users', true);
            }
            if (self::isMissing($missing, 'game_level_id')) {
                self::foreignId($table, 'game_level_id', 'game_levels', true);
            }
            if (self::isMissing($missing, 'status')) {
                $table->string('status')->default('in_progress');
            }
            if (self::isMissing($missing, 'difficulty')) {
                $table->string('difficulty')->nullable();
            }
            if (self::isMissing($missing, 'target_position')) {
                $table->string('target_position')->nullable();
            }
            if (self::isMissing($missing, 'num_questions')) {
                $table->integer('num_questions')->default(0);
            }
            if (self::isMissing($missing, 'response_mode')) {
                $table->string('response_mode')->default('hybrid');
            }
            if (self::isMissing($missing, 'interview_focus')) {
                $table->text('interview_focus')->nullable();
            }
            if (self::isMissing($missing, 'company_persona')) {
                $table->string('company_persona')->nullable();
            }
            if (self::isMissing($missing, 'time_limit')) {
                $table->integer('time_limit')->default(0);
            }
            if (self::isMissing($missing, 'questions')) {
                $table->json('questions')->nullable();
            }
            if (self::isMissing($missing, 'accommodation_profile')) {
                $table->json('accommodation_profile')->nullable();
            }
            if (self::isMissing($missing, 'duration_seconds')) {
                $table->integer('duration_seconds')->nullable();
            }
            if (self::isMissing($missing, 'notes')) {
                $table->text('notes')->nullable();
            }
            if (self::isMissing($missing, 'current_question_index')) {
                $table->integer('current_question_index')->default(0);
            }
            if (self::isMissing($missing, 'session_state')) {
                $table->json('session_state')->nullable();
            }
            if (self::isMissing($missing, 'score')) {
                $table->integer('score')->nullable();
            }
            if (self::isMissing($missing, 'required_score')) {
                $table->integer('required_score')->nullable();
            }
            if (self::isMissing($missing, 'result_status')) {
                $table->string('result_status')->nullable();
            }
            if (self::isMissing($missing, 'goal_breakdown')) {
                $table->json('goal_breakdown')->nullable();
            }
            if (self::isMissing($missing, 'xp_earned')) {
                $table->integer('xp_earned')->default(0);
            }
            if (self::isMissing($missing, 'energy_spent')) {
                $table->integer('energy_spent')->default(0);
            }
            if (self::isMissing($missing, 'energy_remaining')) {
                $table->integer('energy_remaining')->nullable();
            }
            if (self::isMissing($missing, 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (self::isMissing($missing, 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureGameAnswersTable(): void
    {
        if (! Schema::hasTable('game_answers')) {
            Schema::create('game_answers', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'game_session_id', 'game_sessions');
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

            return;
        }

        $missing = self::missingColumns('game_answers', [
            'game_session_id',
            'question_index',
            'question_text',
            'answer_text',
            'is_skipped',
            'response_mode',
            'elapsed_seconds',
            'wpm',
            'voice_duration',
            'filler_words_count',
            'pause_count',
            'confidence_score',
            'eye_contact_score',
            'posture_score',
            'goal_score',
            'clarity_score',
            'relevance_score',
            'grammar_score',
            'professionalism_score',
            'star_method_score',
            'goal_breakdown',
            'goal_notes',
            'created_at',
            'updated_at',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('game_answers', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'game_session_id')) {
                self::foreignId($table, 'game_session_id', 'game_sessions', true);
            }
            if (self::isMissing($missing, 'question_index')) {
                $table->unsignedInteger('question_index')->default(0);
            }
            if (self::isMissing($missing, 'question_text')) {
                $table->text('question_text')->nullable();
            }
            if (self::isMissing($missing, 'answer_text')) {
                $table->text('answer_text')->nullable();
            }
            if (self::isMissing($missing, 'is_skipped')) {
                $table->boolean('is_skipped')->default(false);
            }
            if (self::isMissing($missing, 'response_mode')) {
                $table->string('response_mode')->default('text');
            }
            if (self::isMissing($missing, 'elapsed_seconds')) {
                $table->integer('elapsed_seconds')->default(0);
            }
            if (self::isMissing($missing, 'wpm')) {
                $table->integer('wpm')->default(0);
            }
            if (self::isMissing($missing, 'voice_duration')) {
                $table->integer('voice_duration')->default(0);
            }
            if (self::isMissing($missing, 'filler_words_count')) {
                $table->integer('filler_words_count')->default(0);
            }
            if (self::isMissing($missing, 'pause_count')) {
                $table->integer('pause_count')->default(0);
            }
            if (self::isMissing($missing, 'confidence_score')) {
                $table->integer('confidence_score')->default(0);
            }
            if (self::isMissing($missing, 'eye_contact_score')) {
                $table->integer('eye_contact_score')->default(0);
            }
            if (self::isMissing($missing, 'posture_score')) {
                $table->integer('posture_score')->default(0);
            }
            if (self::isMissing($missing, 'goal_score')) {
                $table->integer('goal_score')->nullable();
            }
            if (self::isMissing($missing, 'clarity_score')) {
                $table->integer('clarity_score')->nullable();
            }
            if (self::isMissing($missing, 'relevance_score')) {
                $table->integer('relevance_score')->nullable();
            }
            if (self::isMissing($missing, 'grammar_score')) {
                $table->integer('grammar_score')->nullable();
            }
            if (self::isMissing($missing, 'professionalism_score')) {
                $table->integer('professionalism_score')->nullable();
            }
            if (self::isMissing($missing, 'star_method_score')) {
                $table->integer('star_method_score')->nullable();
            }
            if (self::isMissing($missing, 'goal_breakdown')) {
                $table->json('goal_breakdown')->nullable();
            }
            if (self::isMissing($missing, 'goal_notes')) {
                $table->text('goal_notes')->nullable();
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureGameCertificatesTable(): void
    {
        if (! Schema::hasTable('game_certificates')) {
            Schema::create('game_certificates', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users');
                self::foreignId($table, 'category_id', 'categories');
                self::foreignId($table, 'final_game_level_id', 'game_levels');
                $table->string('certificate_code')->unique();
                $table->timestamp('issued_at')->useCurrent();
                $table->timestamps();

                $table->unique(['user_id', 'category_id']);
            });

            return;
        }

        $missing = self::missingColumns('game_certificates', [
            'user_id',
            'category_id',
            'final_game_level_id',
            'certificate_code',
            'issued_at',
            'created_at',
            'updated_at',
        ]);

        if ($missing === []) {
            return;
        }

        Schema::table('game_certificates', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'user_id')) {
                self::foreignId($table, 'user_id', 'users', true);
            }
            if (self::isMissing($missing, 'category_id')) {
                self::foreignId($table, 'category_id', 'categories', true);
            }
            if (self::isMissing($missing, 'final_game_level_id')) {
                self::foreignId($table, 'final_game_level_id', 'game_levels', true);
            }
            if (self::isMissing($missing, 'certificate_code')) {
                $table->string('certificate_code')->nullable();
            }
            if (self::isMissing($missing, 'issued_at')) {
                $table->timestamp('issued_at')->nullable();
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function foreignId(Blueprint $table, string $column, string $relatedTable, bool $nullable = false): void
    {
        if (! Schema::hasTable($relatedTable)) {
            $definition = $table->unsignedBigInteger($column);

            if ($nullable) {
                $definition->nullable();
            }

            return;
        }

        $definition = $table->foreignId($column);

        if ($nullable) {
            $definition->nullable();
        }

        $definition->constrained($relatedTable)->cascadeOnDelete();
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private static function missingColumns(string $table, array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn($table, $column)
        ));
    }

    /**
     * @param  array<int, string>  $missing
     */
    private static function isMissing(array $missing, string $column): bool
    {
        return in_array($column, $missing, true);
    }
}
