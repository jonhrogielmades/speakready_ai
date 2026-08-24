<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FeedbackSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredColumns()) {
            return;
        }

        if (! Schema::hasTable('feedback')) {
            if ($createIfMissing && Schema::hasTable('interview_sessions')) {
                self::createTable();
            }

            self::$checked = true;

            return;
        }

        $missing = self::missingColumns(self::requiredColumns());
        if ($missing !== []) {
            Schema::table('feedback', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'interview_session_id')) {
                    self::foreignId($table, 'interview_session_id', 'interview_sessions', true);
                }
                if (self::isMissing($missing, 'strengths')) {
                    $table->text('strengths')->nullable();
                }
                if (self::isMissing($missing, 'weaknesses')) {
                    $table->text('weaknesses')->nullable();
                }
                if (self::isMissing($missing, 'improvement_suggestions')) {
                    $table->text('improvement_suggestions')->nullable();
                }
                if (self::isMissing($missing, 'coaching_summary')) {
                    $table->json('coaching_summary')->nullable();
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
    }

    public static function hasRequiredColumns(): bool
    {
        return Schema::hasTable('feedback')
            && self::missingColumns(self::requiredColumns()) === [];
    }

    private static function createTable(): void
    {
        Schema::create('feedback', function (Blueprint $table): void {
            $table->id();
            self::foreignId($table, 'interview_session_id', 'interview_sessions');
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('improvement_suggestions')->nullable();
            $table->json('coaching_summary')->nullable();
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
            'strengths',
            'weaknesses',
            'improvement_suggestions',
            'coaching_summary',
            'created_at',
            'updated_at',
        ];
    }

    private static function missingColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn('feedback', $column)
        ));
    }

    private static function isMissing(array $missing, string $column): bool
    {
        return in_array($column, $missing, true);
    }
}
