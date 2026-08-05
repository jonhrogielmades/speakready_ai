<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CareerPlanningSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false): void
    {
        if (! $force && self::$checked && self::hasRequiredPracticePlanTable()) {
            return;
        }

        self::ensurePracticePlanItemsTable();

        self::$checked = true;
    }

    public static function hasRequiredPracticePlanTable(): bool
    {
        return Schema::hasTable('practice_plan_items')
            && self::missingColumns('practice_plan_items', self::practicePlanColumns()) === [];
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
