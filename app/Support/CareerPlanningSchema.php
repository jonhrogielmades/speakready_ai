<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerPlanningSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false): void
    {
        if (! $force && self::$checked && self::hasRequiredTables()) {
            return;
        }

        self::ensureJobApplicationsTable();
        self::ensureInterviewPacksTable();
        self::ensurePracticePlanItemsTable();

        self::$checked = true;
    }

    public static function hasRequiredTables(): bool
    {
        return self::hasRequiredJobApplicationsTable()
            && self::hasRequiredInterviewPacksTable()
            && self::hasRequiredPracticePlanTable();
    }

    public static function hasRequiredJobApplicationsTable(): bool
    {
        return Schema::hasTable('job_applications')
            && self::missingColumns('job_applications', self::jobApplicationColumns()) === [];
    }

    public static function hasRequiredInterviewPacksTable(): bool
    {
        return Schema::hasTable('interview_packs')
            && self::missingColumns('interview_packs', self::interviewPackColumns()) === [];
    }

    public static function hasRequiredPracticePlanTable(): bool
    {
        return Schema::hasTable('practice_plan_items')
            && self::missingColumns('practice_plan_items', self::practicePlanColumns()) === [];
    }

    private static function ensureJobApplicationsTable(): void
    {
        if (! Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users');
                $table->string('company_name');
                $table->string('job_title');
                $table->string('status')->default('tracking');
                $table->string('interview_stage')->nullable();
                $table->date('interview_date')->nullable();
                $table->string('source_url')->nullable();
                $table->longText('resume_text')->nullable();
                $table->longText('job_description')->nullable();
                $table->integer('match_score')->default(0);
                $table->json('matched_keywords')->nullable();
                $table->json('missing_keywords')->nullable();
                $table->json('smart_plan')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('job_applications', self::jobApplicationColumns());
        if ($missing === []) {
            return;
        }

        Schema::table('job_applications', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'user_id')) {
                self::foreignId($table, 'user_id', 'users', true);
            }
            if (self::isMissing($missing, 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (self::isMissing($missing, 'job_title')) {
                $table->string('job_title')->nullable();
            }
            if (self::isMissing($missing, 'status')) {
                $table->string('status')->default('tracking');
            }
            if (self::isMissing($missing, 'interview_stage')) {
                $table->string('interview_stage')->nullable();
            }
            if (self::isMissing($missing, 'interview_date')) {
                $table->date('interview_date')->nullable();
            }
            if (self::isMissing($missing, 'source_url')) {
                $table->string('source_url')->nullable();
            }
            if (self::isMissing($missing, 'resume_text')) {
                $table->longText('resume_text')->nullable();
            }
            if (self::isMissing($missing, 'job_description')) {
                $table->longText('job_description')->nullable();
            }
            if (self::isMissing($missing, 'match_score')) {
                $table->integer('match_score')->default(0);
            }
            if (self::isMissing($missing, 'matched_keywords')) {
                $table->json('matched_keywords')->nullable();
            }
            if (self::isMissing($missing, 'missing_keywords')) {
                $table->json('missing_keywords')->nullable();
            }
            if (self::isMissing($missing, 'smart_plan')) {
                $table->json('smart_plan')->nullable();
            }
            if (self::isMissing($missing, 'notes')) {
                $table->text('notes')->nullable();
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureInterviewPacksTable(): void
    {
        if (! Schema::hasTable('interview_packs')) {
            Schema::create('interview_packs', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('company')->nullable();
                $table->string('role_family')->nullable();
                $table->string('difficulty')->default('medium');
                $table->string('interview_focus')->default('General Practice');
                $table->string('company_persona')->nullable();
                $table->json('question_types')->nullable();
                $table->json('sample_questions')->nullable();
                $table->text('description')->nullable();
                $table->boolean('pressure_mode')->default(false);
                $table->string('status')->default('active');
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('interview_packs', self::interviewPackColumns());
        if ($missing === []) {
            return;
        }

        Schema::table('interview_packs', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'name')) {
                $table->string('name')->nullable();
            }
            if (self::isMissing($missing, 'slug')) {
                $table->string('slug')->nullable();
            }
            if (self::isMissing($missing, 'company')) {
                $table->string('company')->nullable();
            }
            if (self::isMissing($missing, 'role_family')) {
                $table->string('role_family')->nullable();
            }
            if (self::isMissing($missing, 'difficulty')) {
                $table->string('difficulty')->default('medium');
            }
            if (self::isMissing($missing, 'interview_focus')) {
                $table->string('interview_focus')->default('General Practice');
            }
            if (self::isMissing($missing, 'company_persona')) {
                $table->string('company_persona')->nullable();
            }
            if (self::isMissing($missing, 'question_types')) {
                $table->json('question_types')->nullable();
            }
            if (self::isMissing($missing, 'sample_questions')) {
                $table->json('sample_questions')->nullable();
            }
            if (self::isMissing($missing, 'description')) {
                $table->text('description')->nullable();
            }
            if (self::isMissing($missing, 'pressure_mode')) {
                $table->boolean('pressure_mode')->default(false);
            }
            if (self::isMissing($missing, 'status')) {
                $table->string('status')->default('active');
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensurePracticePlanItemsTable(): void
    {
        if (! Schema::hasTable('practice_plan_items')) {
            Schema::create('practice_plan_items', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users');
                self::foreignId($table, 'job_application_id', 'job_applications', true);
                self::foreignId($table, 'interview_session_id', 'interview_sessions', true);
                $table->integer('day_number')->default(1);
                $table->date('due_date')->nullable();
                $table->string('type')->default('practice');
                $table->string('title');
                $table->text('task');
                $table->json('metadata')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('practice_plan_items', self::practicePlanColumns());

        if ($missing === []) {
            return;
        }

        Schema::table('practice_plan_items', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'user_id')) {
                self::foreignId($table, 'user_id', 'users', true);
            }
            if (self::isMissing($missing, 'job_application_id')) {
                self::foreignId($table, 'job_application_id', 'job_applications', true);
            }
            if (self::isMissing($missing, 'interview_session_id')) {
                self::foreignId($table, 'interview_session_id', 'interview_sessions', true);
            }
            if (self::isMissing($missing, 'day_number')) {
                $table->integer('day_number')->default(1);
            }
            if (self::isMissing($missing, 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (self::isMissing($missing, 'type')) {
                $table->string('type')->default('practice');
            }
            if (self::isMissing($missing, 'title')) {
                $table->string('title')->nullable();
            }
            if (self::isMissing($missing, 'task')) {
                $table->text('task')->nullable();
            }
            if (self::isMissing($missing, 'metadata')) {
                $table->json('metadata')->nullable();
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
    private static function jobApplicationColumns(): array
    {
        return [
            'user_id',
            'company_name',
            'job_title',
            'status',
            'interview_stage',
            'interview_date',
            'source_url',
            'resume_text',
            'job_description',
            'match_score',
            'matched_keywords',
            'missing_keywords',
            'smart_plan',
            'notes',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function interviewPackColumns(): array
    {
        return [
            'name',
            'slug',
            'company',
            'role_family',
            'difficulty',
            'interview_focus',
            'company_persona',
            'question_types',
            'sample_questions',
            'description',
            'pressure_mode',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function practicePlanColumns(): array
    {
        return [
            'user_id',
            'job_application_id',
            'interview_session_id',
            'day_number',
            'due_date',
            'type',
            'title',
            'task',
            'metadata',
            'completed_at',
            'created_at',
            'updated_at',
        ];
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
