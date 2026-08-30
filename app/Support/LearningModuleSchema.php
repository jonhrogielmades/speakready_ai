<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LearningModuleSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredTables()) {
            return;
        }

        if ($createIfMissing) {
            self::ensureLearningModulesTable();
            self::ensureLearningProgressTable();
            self::ensureModuleChaptersTable();
            self::ensureModuleResourcesTable();
            self::ensureModuleQuizzesTable();
            self::ensureModuleQuizQuestionsTable();
            self::ensureModulePracticeActivitiesTable();
            self::ensureLearningModuleGameLevelTable();
        }

        self::$checked = true;
    }

    public static function hasRequiredTables(): bool
    {
        return Schema::hasTable('learning_modules')
            && Schema::hasTable('learning_progress')
            && Schema::hasTable('module_chapters')
            && Schema::hasTable('module_resources')
            && Schema::hasTable('module_quizzes')
            && Schema::hasTable('module_quiz_questions')
            && Schema::hasTable('module_practice_activities')
            && Schema::hasTable('learning_module_game_level')
            && self::missingColumns('learning_modules', self::learningModuleColumns()) === []
            && self::missingColumns('learning_progress', self::learningProgressColumns()) === []
            && self::missingColumns('module_chapters', self::moduleChapterColumns()) === []
            && self::missingColumns('module_resources', self::moduleResourceColumns()) === []
            && self::missingColumns('module_quizzes', self::moduleQuizColumns()) === []
            && self::missingColumns('module_quiz_questions', self::moduleQuizQuestionColumns()) === []
            && self::missingColumns('module_practice_activities', self::modulePracticeActivityColumns()) === []
            && self::missingColumns('learning_module_game_level', self::learningModuleGameLevelColumns()) === [];
    }

    private static function ensureLearningModulesTable(): void
    {
        if (! Schema::hasTable('learning_modules')) {
            Schema::create('learning_modules', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type')->default('article');
                $table->string('career_path')->nullable();
                $table->string('url')->nullable();
                $table->string('category')->nullable();
                $table->string('difficulty')->nullable();
                $table->string('status')->default('draft');
                $table->integer('views')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->json('mapped_skills')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('learning_modules', self::learningModuleColumns());

        if ($missing !== []) {
            Schema::table('learning_modules', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'title')) {
                    $table->string('title')->nullable();
                }
                if (self::isMissing($missing, 'description')) {
                    $table->text('description')->nullable();
                }
                if (self::isMissing($missing, 'type')) {
                    $table->string('type')->default('article');
                }
                if (self::isMissing($missing, 'career_path')) {
                    $table->string('career_path')->nullable();
                }
                if (self::isMissing($missing, 'url')) {
                    $table->string('url')->nullable();
                }
                if (self::isMissing($missing, 'category')) {
                    $table->string('category')->nullable();
                }
                if (self::isMissing($missing, 'difficulty')) {
                    $table->string('difficulty')->nullable();
                }
                if (self::isMissing($missing, 'status')) {
                    $table->string('status')->default('draft');
                }
                if (self::isMissing($missing, 'views')) {
                    $table->integer('views')->default(0);
                }
                if (self::isMissing($missing, 'is_featured')) {
                    $table->boolean('is_featured')->default(false);
                }
                if (self::isMissing($missing, 'mapped_skills')) {
                    $table->json('mapped_skills')->nullable();
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        DB::table('learning_modules')->whereNull('type')->update(['type' => 'article']);
        DB::table('learning_modules')->whereNull('status')->update(['status' => 'draft']);
        DB::table('learning_modules')->whereNull('views')->update(['views' => 0]);
        DB::table('learning_modules')->whereNull('is_featured')->update(['is_featured' => false]);
    }

    private static function ensureLearningProgressTable(): void
    {
        if (! Schema::hasTable('learning_progress')) {
            Schema::create('learning_progress', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users', true);
                self::foreignId($table, 'learning_module_id', 'learning_modules', true);
                $table->string('status')->default('enrolled');
                $table->integer('progress_percentage')->default(0);
                $table->integer('quiz_score')->nullable();
                $table->decimal('learning_hours', 8, 2)->default(0);
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('learning_progress', self::learningProgressColumns());

        if ($missing !== []) {
            Schema::table('learning_progress', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'user_id')) {
                    self::foreignId($table, 'user_id', 'users', true);
                }
                if (self::isMissing($missing, 'learning_module_id')) {
                    self::foreignId($table, 'learning_module_id', 'learning_modules', true);
                }
                if (self::isMissing($missing, 'status')) {
                    $table->string('status')->default('enrolled');
                }
                if (self::isMissing($missing, 'progress_percentage')) {
                    $table->integer('progress_percentage')->default(0);
                }
                if (self::isMissing($missing, 'quiz_score')) {
                    $table->integer('quiz_score')->nullable();
                }
                if (self::isMissing($missing, 'learning_hours')) {
                    $table->decimal('learning_hours', 8, 2)->default(0);
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        DB::table('learning_progress')->whereNull('status')->update(['status' => 'enrolled']);
        DB::table('learning_progress')->whereNull('progress_percentage')->update(['progress_percentage' => 0]);
        DB::table('learning_progress')->whereNull('learning_hours')->update(['learning_hours' => 0]);
    }

    private static function ensureModuleChaptersTable(): void
    {
        if (! Schema::hasTable('module_chapters')) {
            Schema::create('module_chapters', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'learning_module_id', 'learning_modules');
                $table->string('title');
                $table->longText('content')->nullable();
                $table->string('video_url')->nullable();
                $table->string('video_duration')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('module_chapters', self::moduleChapterColumns());

        if ($missing !== []) {
            Schema::table('module_chapters', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'learning_module_id')) {
                    self::foreignId($table, 'learning_module_id', 'learning_modules', true);
                }
                if (self::isMissing($missing, 'title')) {
                    $table->string('title')->nullable();
                }
                if (self::isMissing($missing, 'content')) {
                    $table->longText('content')->nullable();
                }
                if (self::isMissing($missing, 'video_url')) {
                    $table->string('video_url')->nullable();
                }
                if (self::isMissing($missing, 'video_duration')) {
                    $table->string('video_duration')->nullable();
                }
                if (self::isMissing($missing, 'order')) {
                    $table->integer('order')->default(0);
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    private static function ensureModuleResourcesTable(): void
    {
        if (! Schema::hasTable('module_resources')) {
            Schema::create('module_resources', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'learning_module_id', 'learning_modules');
                $table->string('title');
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('module_resources', self::moduleResourceColumns());

        if ($missing !== []) {
            Schema::table('module_resources', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'learning_module_id')) {
                    self::foreignId($table, 'learning_module_id', 'learning_modules', true);
                }
                if (self::isMissing($missing, 'title')) {
                    $table->string('title')->nullable();
                }
                if (self::isMissing($missing, 'file_path')) {
                    $table->string('file_path')->nullable();
                }
                if (self::isMissing($missing, 'file_type')) {
                    $table->string('file_type')->nullable();
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    private static function ensureModuleQuizzesTable(): void
    {
        if (! Schema::hasTable('module_quizzes')) {
            Schema::create('module_quizzes', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'learning_module_id', 'learning_modules');
                $table->string('title');
                $table->integer('passing_score')->default(70);
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('module_quizzes', self::moduleQuizColumns());

        if ($missing !== []) {
            Schema::table('module_quizzes', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'learning_module_id')) {
                    self::foreignId($table, 'learning_module_id', 'learning_modules', true);
                }
                if (self::isMissing($missing, 'title')) {
                    $table->string('title')->nullable();
                }
                if (self::isMissing($missing, 'passing_score')) {
                    $table->integer('passing_score')->default(70);
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    private static function ensureModuleQuizQuestionsTable(): void
    {
        if (! Schema::hasTable('module_quiz_questions')) {
            Schema::create('module_quiz_questions', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'module_quiz_id', 'module_quizzes');
                $table->string('type');
                $table->text('question_text');
                $table->json('options')->nullable();
                $table->string('correct_answer');
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('module_quiz_questions', self::moduleQuizQuestionColumns());

        if ($missing !== []) {
            Schema::table('module_quiz_questions', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'module_quiz_id')) {
                    self::foreignId($table, 'module_quiz_id', 'module_quizzes', true);
                }
                if (self::isMissing($missing, 'type')) {
                    $table->string('type')->nullable();
                }
                if (self::isMissing($missing, 'question_text')) {
                    $table->text('question_text')->nullable();
                }
                if (self::isMissing($missing, 'options')) {
                    $table->json('options')->nullable();
                }
                if (self::isMissing($missing, 'correct_answer')) {
                    $table->string('correct_answer')->nullable();
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    private static function ensureModulePracticeActivitiesTable(): void
    {
        if (! Schema::hasTable('module_practice_activities')) {
            Schema::create('module_practice_activities', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'learning_module_id', 'learning_modules');
                $table->string('title');
                $table->string('type');
                $table->text('description')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('module_practice_activities', self::modulePracticeActivityColumns());

        if ($missing !== []) {
            Schema::table('module_practice_activities', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'learning_module_id')) {
                    self::foreignId($table, 'learning_module_id', 'learning_modules', true);
                }
                if (self::isMissing($missing, 'title')) {
                    $table->string('title')->nullable();
                }
                if (self::isMissing($missing, 'type')) {
                    $table->string('type')->nullable();
                }
                if (self::isMissing($missing, 'description')) {
                    $table->text('description')->nullable();
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    private static function ensureLearningModuleGameLevelTable(): void
    {
        if (! Schema::hasTable('learning_module_game_level')) {
            Schema::create('learning_module_game_level', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'learning_module_id', 'learning_modules');
                self::foreignId($table, 'game_level_id', 'game_levels');
                $table->timestamps();
            });

            self::copyLegacyArenaPivotRows();

            return;
        }

        $missing = self::missingColumns('learning_module_game_level', self::learningModuleGameLevelColumns());

        if ($missing !== []) {
            Schema::table('learning_module_game_level', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'learning_module_id')) {
                    self::foreignId($table, 'learning_module_id', 'learning_modules', true);
                }
                if (self::isMissing($missing, 'game_level_id')) {
                    self::foreignId($table, 'game_level_id', 'game_levels', true);
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        self::normalizeGameLevelPivotColumn();
    }

    private static function copyLegacyArenaPivotRows(): void
    {
        if (! Schema::hasTable('learning_module_arena_level')
            || ! Schema::hasColumn('learning_module_arena_level', 'learning_module_id')
            || ! Schema::hasColumn('learning_module_arena_level', 'arena_level_id')
            || DB::table('learning_module_game_level')->exists()) {
            return;
        }

        $legacyRows = DB::table('learning_module_arena_level')->get();

        foreach ($legacyRows as $row) {
            DB::table('learning_module_game_level')->insert([
                'learning_module_id' => $row->learning_module_id,
                'game_level_id' => $row->arena_level_id,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    private static function normalizeGameLevelPivotColumn(): void
    {
        if (! Schema::hasColumn('learning_module_game_level', 'arena_level_id')
            || ! Schema::hasColumn('learning_module_game_level', 'game_level_id')) {
            return;
        }

        DB::table('learning_module_game_level')
            ->whereNull('game_level_id')
            ->update(['game_level_id' => DB::raw('arena_level_id')]);
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
     * @return array<int, string>
     */
    private static function learningModuleColumns(): array
    {
        return [
            'title',
            'description',
            'type',
            'career_path',
            'url',
            'category',
            'difficulty',
            'status',
            'views',
            'is_featured',
            'mapped_skills',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function learningProgressColumns(): array
    {
        return [
            'user_id',
            'learning_module_id',
            'status',
            'progress_percentage',
            'quiz_score',
            'learning_hours',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function moduleChapterColumns(): array
    {
        return [
            'learning_module_id',
            'title',
            'content',
            'video_url',
            'video_duration',
            'order',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function moduleResourceColumns(): array
    {
        return [
            'learning_module_id',
            'title',
            'file_path',
            'file_type',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function moduleQuizColumns(): array
    {
        return [
            'learning_module_id',
            'title',
            'passing_score',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function moduleQuizQuestionColumns(): array
    {
        return [
            'module_quiz_id',
            'type',
            'question_text',
            'options',
            'correct_answer',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function modulePracticeActivityColumns(): array
    {
        return [
            'learning_module_id',
            'title',
            'type',
            'description',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function learningModuleGameLevelColumns(): array
    {
        return [
            'learning_module_id',
            'game_level_id',
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
        if (! Schema::hasTable($table)) {
            return $columns;
        }

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
