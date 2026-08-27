<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InterviewSessionSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false): void
    {
        if (! $force && self::$checked && self::hasRequiredColumns()) {
            return;
        }

        if (! Schema::hasTable('interview_sessions')) {
            self::$checked = true;

            return;
        }

        $missing = self::missingColumns(self::requiredColumns());
        if ($missing !== []) {
            Schema::table('interview_sessions', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (self::isMissing($missing, 'duration_seconds')) {
                    $table->integer('duration_seconds')->default(0);
                }
                if (self::isMissing($missing, 'interviewer_strictness')) {
                    $table->string('interviewer_strictness')->default('neutral');
                }
                if (self::isMissing($missing, 'live_feedback_mode')) {
                    $table->string('live_feedback_mode')->default('coaching');
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
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (Schema::hasColumn('interview_sessions', 'assessment_mode')) {
            DB::table('interview_sessions')->whereNull('assessment_mode')->update(['assessment_mode' => 'legacy']);
        }

        self::$checked = true;
    }

    public static function hasRequiredColumns(): bool
    {
        return Schema::hasTable('interview_sessions')
            && self::missingColumns(self::requiredColumns()) === [];
    }

    /**
     * @return array<int, string>
     */
    private static function requiredColumns(): array
    {
        return [
            'notes',
            'duration_seconds',
            'interviewer_strictness',
            'live_feedback_mode',
            'current_question_index',
            'session_state',
            'action_plan',
            'assessment_mode',
            'interview_format',
            'accommodation_profile',
            'score_eligible',
            'created_at',
            'updated_at',
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
}
