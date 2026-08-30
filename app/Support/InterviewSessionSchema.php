<?php

namespace App\Support;

use App\Models\InterviewSession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InterviewSessionSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredColumns()) {
            return;
        }

        if (! Schema::hasTable('interview_sessions')) {
            if ($createIfMissing) {
                self::createTable();
            }

            self::$checked = true;
            self::flushModelColumnCache();

            return;
        }

        $missing = self::missingColumns(self::requiredColumns());
        if ($missing !== []) {
            Schema::table('interview_sessions', function (Blueprint $table) use ($missing): void {
                self::addMissingColumns($table, $missing);
            });
        }

        self::normalizeDefaults();

        self::$checked = true;
        self::flushModelColumnCache();
    }

    public static function hasRequiredColumns(): bool
    {
        return Schema::hasTable('interview_sessions')
            && self::missingColumns(self::requiredColumns()) === [];
    }

    private static function createTable(): void
    {
        Schema::create('interview_sessions', function (Blueprint $table): void {
            $table->id();
            self::foreignId($table, 'user_id', 'users');
            self::foreignId($table, 'game_level_id', 'game_levels', true);
            self::foreignId($table, 'job_application_id', 'job_applications', true);
            self::foreignId($table, 'interview_pack_id', 'interview_packs', true);
            self::foreignId($table, 'category_id', 'categories', true);
            $table->string('difficulty')->default('medium');
            $table->string('target_position')->nullable();
            $table->longText('resume_text')->nullable();
            $table->longText('job_description')->nullable();
            $table->integer('num_questions')->default(5);
            $table->string('coach_focus_mode')->default('balanced');
            $table->string('response_mode')->default('text');
            $table->string('interview_focus')->nullable();
            $table->string('company_persona')->nullable();
            $table->string('interviewer_strictness')->default('neutral');
            $table->integer('time_limit')->default(0);
            $table->string('question_types')->nullable();
            $table->string('ai_assistance_level')->default('standard');
            $table->string('live_feedback_mode')->default('coaching');
            $table->boolean('pressure_mode')->default(false);
            $table->string('assessment_mode')->default('legacy');
            $table->string('interview_format')->default('standard');
            $table->json('accommodation_profile')->nullable();
            $table->boolean('score_eligible')->default(false);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->integer('current_question_index')->default(0);
            $table->longText('session_state')->nullable();
            $table->json('action_plan')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->string('flag_reason')->nullable();
            $table->string('share_token')->nullable()->unique();
            $table->timestamp('share_expires_at')->nullable();
            $table->string('share_password_hash')->nullable();
            $table->json('share_permissions')->nullable();
            $table->boolean('share_hide_sensitive')->default(true);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * @param  array<int, string>  $missing
     */
    private static function addMissingColumns(Blueprint $table, array $missing): void
    {
        if (self::isMissing($missing, 'user_id')) {
            self::foreignId($table, 'user_id', 'users', true);
        }
        if (self::isMissing($missing, 'game_level_id')) {
            self::foreignId($table, 'game_level_id', 'game_levels', true);
        }
        if (self::isMissing($missing, 'job_application_id')) {
            self::foreignId($table, 'job_application_id', 'job_applications', true);
        }
        if (self::isMissing($missing, 'interview_pack_id')) {
            self::foreignId($table, 'interview_pack_id', 'interview_packs', true);
        }
        if (self::isMissing($missing, 'category_id')) {
            self::foreignId($table, 'category_id', 'categories', true);
        }
        if (self::isMissing($missing, 'difficulty')) {
            $table->string('difficulty')->default('medium');
        }
        if (self::isMissing($missing, 'target_position')) {
            $table->string('target_position')->nullable();
        }
        if (self::isMissing($missing, 'resume_text')) {
            $table->longText('resume_text')->nullable();
        }
        if (self::isMissing($missing, 'job_description')) {
            $table->longText('job_description')->nullable();
        }
        if (self::isMissing($missing, 'num_questions')) {
            $table->integer('num_questions')->default(5);
        }
        if (self::isMissing($missing, 'coach_focus_mode')) {
            $table->string('coach_focus_mode')->default('balanced');
        }
        if (self::isMissing($missing, 'response_mode')) {
            $table->string('response_mode')->default('text');
        }
        if (self::isMissing($missing, 'interview_focus')) {
            $table->string('interview_focus')->nullable();
        }
        if (self::isMissing($missing, 'company_persona')) {
            $table->string('company_persona')->nullable();
        }
        if (self::isMissing($missing, 'interviewer_strictness')) {
            $table->string('interviewer_strictness')->default('neutral');
        }
        if (self::isMissing($missing, 'time_limit')) {
            $table->integer('time_limit')->default(0);
        }
        if (self::isMissing($missing, 'question_types')) {
            $table->string('question_types')->nullable();
        }
        if (self::isMissing($missing, 'ai_assistance_level')) {
            $table->string('ai_assistance_level')->default('standard');
        }
        if (self::isMissing($missing, 'live_feedback_mode')) {
            $table->string('live_feedback_mode')->default('coaching');
        }
        if (self::isMissing($missing, 'pressure_mode')) {
            $table->boolean('pressure_mode')->default(false);
        }
        if (self::isMissing($missing, 'assessment_mode')) {
            $table->string('assessment_mode')->default('legacy');
        }
        if (self::isMissing($missing, 'interview_format')) {
            $table->string('interview_format')->default('standard');
        }
        if (self::isMissing($missing, 'accommodation_profile')) {
            $table->json('accommodation_profile')->nullable();
        }
        if (self::isMissing($missing, 'score_eligible')) {
            $table->boolean('score_eligible')->default(false);
        }
        if (self::isMissing($missing, 'status')) {
            $table->string('status')->default('pending');
        }
        if (self::isMissing($missing, 'notes')) {
            $table->text('notes')->nullable();
        }
        if (self::isMissing($missing, 'duration_seconds')) {
            $table->integer('duration_seconds')->default(0);
        }
        if (self::isMissing($missing, 'current_question_index')) {
            $table->integer('current_question_index')->default(0);
        }
        if (self::isMissing($missing, 'session_state')) {
            $table->longText('session_state')->nullable();
        }
        if (self::isMissing($missing, 'action_plan')) {
            $table->json('action_plan')->nullable();
        }
        if (self::isMissing($missing, 'is_archived')) {
            $table->boolean('is_archived')->default(false);
        }
        if (self::isMissing($missing, 'flag_reason')) {
            $table->string('flag_reason')->nullable();
        }
        if (self::isMissing($missing, 'share_token')) {
            $table->string('share_token')->nullable();
        }
        if (self::isMissing($missing, 'share_expires_at')) {
            $table->timestamp('share_expires_at')->nullable();
        }
        if (self::isMissing($missing, 'share_password_hash')) {
            $table->string('share_password_hash')->nullable();
        }
        if (self::isMissing($missing, 'share_permissions')) {
            $table->json('share_permissions')->nullable();
        }
        if (self::isMissing($missing, 'share_hide_sensitive')) {
            $table->boolean('share_hide_sensitive')->default(true);
        }
        if (self::isMissing($missing, 'is_public')) {
            $table->boolean('is_public')->default(false);
        }
        if (self::isMissing($missing, 'created_at')) {
            $table->timestamp('created_at')->nullable();
        }
        if (self::isMissing($missing, 'updated_at')) {
            $table->timestamp('updated_at')->nullable();
        }
    }

    private static function normalizeDefaults(): void
    {
        foreach (self::columnDefaults() as $column => $default) {
            if (Schema::hasColumn('interview_sessions', $column)) {
                DB::table('interview_sessions')->whereNull($column)->update([$column => $default]);
            }
        }
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

    /**
     * @return array<int, string>
     */
    private static function requiredColumns(): array
    {
        return [
            'user_id',
            'game_level_id',
            'job_application_id',
            'interview_pack_id',
            'category_id',
            'difficulty',
            'target_position',
            'resume_text',
            'job_description',
            'num_questions',
            'coach_focus_mode',
            'response_mode',
            'interview_focus',
            'company_persona',
            'interviewer_strictness',
            'time_limit',
            'question_types',
            'ai_assistance_level',
            'live_feedback_mode',
            'pressure_mode',
            'assessment_mode',
            'interview_format',
            'accommodation_profile',
            'score_eligible',
            'status',
            'notes',
            'duration_seconds',
            'current_question_index',
            'session_state',
            'action_plan',
            'is_archived',
            'flag_reason',
            'share_token',
            'share_expires_at',
            'share_password_hash',
            'share_permissions',
            'share_hide_sensitive',
            'is_public',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function columnDefaults(): array
    {
        return [
            'difficulty' => 'medium',
            'num_questions' => 5,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'interviewer_strictness' => 'neutral',
            'time_limit' => 0,
            'ai_assistance_level' => 'standard',
            'live_feedback_mode' => 'coaching',
            'pressure_mode' => false,
            'assessment_mode' => 'legacy',
            'interview_format' => 'standard',
            'score_eligible' => false,
            'status' => 'pending',
            'duration_seconds' => 0,
            'current_question_index' => 0,
            'is_archived' => false,
            'share_hide_sensitive' => true,
            'is_public' => false,
        ];
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private static function missingColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn('interview_sessions', $column)
        ));
    }

    /**
     * @param  array<int, string>  $missing
     */
    private static function isMissing(array $missing, string $column): bool
    {
        return in_array($column, $missing, true);
    }

    private static function flushModelColumnCache(): void
    {
        if (method_exists(InterviewSession::class, 'flushColumnCache')) {
            InterviewSession::flushColumnCache();
        }
    }
}
