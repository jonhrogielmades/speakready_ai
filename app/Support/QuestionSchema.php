<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuestionSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredColumns()) {
            return;
        }

        if (! Schema::hasTable('questions')) {
            if ($createIfMissing && Schema::hasTable('categories')) {
                self::createTable();
            }

            self::$checked = true;

            return;
        }

        $missing = self::missingColumns([
            'category_id',
            'question_text',
            'difficulty',
            'interview_session_id',
            'type',
            'status',
            'expected_guide',
            'mapped_skills',
            'source_name',
            'source_url',
            'source_type',
            'created_at',
            'updated_at',
        ]);

        if ($missing !== []) {
            Schema::table('questions', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'category_id')) {
                    self::foreignId($table, 'category_id', 'categories', true);
                }
                if (self::isMissing($missing, 'question_text')) {
                    $table->text('question_text')->nullable();
                }
                if (self::isMissing($missing, 'difficulty')) {
                    $table->string('difficulty')->default('medium');
                }
                if (self::isMissing($missing, 'interview_session_id')) {
                    self::foreignId($table, 'interview_session_id', 'interview_sessions', true);
                }
                if (self::isMissing($missing, 'type')) {
                    $table->string('type')->default('Behavioral');
                }
                if (self::isMissing($missing, 'status')) {
                    $table->string('status')->default('active');
                }
                if (self::isMissing($missing, 'expected_guide')) {
                    $table->text('expected_guide')->nullable();
                }
                if (self::isMissing($missing, 'mapped_skills')) {
                    $table->json('mapped_skills')->nullable();
                }
                if (self::isMissing($missing, 'source_name')) {
                    $table->string('source_name')->nullable();
                }
                if (self::isMissing($missing, 'source_url')) {
                    $table->text('source_url')->nullable();
                }
                if (self::isMissing($missing, 'source_type')) {
                    $table->string('source_type')->nullable();
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
        return Schema::hasTable('questions')
            && self::missingColumns([
                'category_id',
                'question_text',
                'difficulty',
                'interview_session_id',
                'type',
                'status',
                'expected_guide',
                'mapped_skills',
                'source_name',
                'source_url',
                'source_type',
                'created_at',
                'updated_at',
            ]) === [];
    }

    private static function createTable(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            self::foreignId($table, 'category_id', 'categories');
            $table->text('question_text');
            $table->string('difficulty')->default('medium');
            self::foreignId($table, 'interview_session_id', 'interview_sessions', true);
            $table->string('type')->default('Behavioral');
            $table->string('status')->default('active');
            $table->text('expected_guide')->nullable();
            $table->json('mapped_skills')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_type')->nullable();
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

            $definition->constrained($relatedTable)->cascadeOnDelete();

            return;
        }

        $definition = $table->unsignedBigInteger($column);

        if ($nullable) {
            $definition->nullable();
        }
    }

    private static function missingColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn('questions', $column)
        ));
    }

    private static function isMissing(array $missing, string $column): bool
    {
        return in_array($column, $missing, true);
    }
}
