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
            'feedback',
            ['interview_session_id'],
            'feedback_session_unique',
            'Multiple feedback rows exist for one interview session. Resolve those rows before applying the feedback uniqueness constraint.'
        );

        $this->addUniqueIndexAfterDuplicateCheck(
            'interview_answers',
            ['retry_of_answer_id', 'attempt_number'],
            'interview_answers_retry_attempt_unique',
            'Duplicate retry attempt numbers exist for one interview answer. Resolve those rows before applying the retry uniqueness constraint.',
            fn ($query) => $query->whereNotNull('retry_of_answer_id')
        );

        $this->addIndex(
            'interview_sessions',
            ['user_id', 'status', 'created_at'],
            'interview_sessions_user_status_created_idx'
        );

        $this->addIndex(
            'chatbot_conversations',
            ['user_id', 'updated_at'],
            'chatbot_conversations_user_updated_idx'
        );

        $this->addIndex(
            'game_sessions',
            ['user_id', 'status', 'created_at'],
            'game_sessions_user_status_created_idx'
        );
    }

    public function down(): void
    {
        $this->dropIndex('feedback', 'feedback_session_unique');
        $this->dropIndex('interview_answers', 'interview_answers_retry_attempt_unique');
        $this->dropIndex('interview_sessions', 'interview_sessions_user_status_created_idx');
        $this->dropIndex('chatbot_conversations', 'chatbot_conversations_user_updated_idx');
        $this->dropIndex('game_sessions', 'game_sessions_user_status_created_idx');
    }

    private function addUniqueIndexAfterDuplicateCheck(
        string $table,
        array $columns,
        string $index,
        string $errorMessage,
        ?callable $scope = null
    ): void {
        if (! $this->hasColumns($table, $columns) || $this->hasIndex($table, $index)) {
            return;
        }

        $duplicates = DB::table($table)
            ->select($columns)
            ->when($scope !== null, $scope)
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicates) {
            throw new RuntimeException($errorMessage);
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->unique($columns, $index);
        });
    }

    private function addIndex(string $table, array $columns, string $index): void
    {
        if (! $this->hasColumns($table, $columns) || $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndex(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function hasColumns(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            return collect(Schema::getIndexes($table))
                ->contains(fn (array $existing): bool => ($existing['name'] ?? null) === $index);
        } catch (Throwable) {
            return false;
        }
    }
};
