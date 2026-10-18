<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addUniqueIndexAfterDuplicateCheck(
            'profiles',
            'user_id',
            'profiles_user_id_unique',
            'Duplicate profile rows exist. Resolve them before applying the profiles uniqueness constraint.'
        );

        $this->addUniqueIndexAfterDuplicateCheck(
            'scores',
            'interview_session_id',
            'scores_interview_session_id_unique',
            'Duplicate score rows exist. Resolve them before applying the scores uniqueness constraint.'
        );

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'google_id')) {
            $this->addUniqueIndexAfterDuplicateCheck(
                'users',
                'google_id',
                'users_google_id_unique',
                'Duplicate Google account identifiers exist. Resolve them before applying the Google ID uniqueness constraint.',
                true
            );
        }

        if (Schema::hasTable('interview_answers')) {
            $this->addIndexIfMissing(
                'interview_answers',
                ['interview_session_id', 'question_id', 'retry_of_answer_id'],
                'interview_answers_session_question_retry_index'
            );
        }
    }

    public function down(): void
    {
        $this->dropIndexIfPresent('profiles', 'profiles_user_id_unique');
        $this->dropIndexIfPresent('scores', 'scores_interview_session_id_unique');
        $this->dropIndexIfPresent('users', 'users_google_id_unique');
        $this->dropIndexIfPresent('interview_answers', 'interview_answers_session_question_retry_index');
    }

    private function addUniqueIndexAfterDuplicateCheck(
        string $table,
        string $column,
        string $index,
        string $errorMessage,
        bool $ignoreNulls = false
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || $this->hasIndex($table, $index)) {
            return;
        }

        $duplicates = DB::table($table)
            ->select($column)
            ->when($ignoreNulls, fn ($query) => $query->whereNotNull($column))
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicates) {
            throw new RuntimeException($errorMessage);
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $index): void {
            $blueprint->unique($column, $index);
        });
    }

    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndexIfPresent(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            return false;
        }

        try {
            foreach (Schema::getIndexes($table) as $existingIndex) {
                if (($existingIndex['name'] ?? null) === $index) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }
};
