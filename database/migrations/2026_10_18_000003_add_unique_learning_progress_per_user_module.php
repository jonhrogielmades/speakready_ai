<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_progress')) {
            return;
        }

        // Merge any legacy duplicate rows before adding the invariant. Keep
        // the furthest verified milestone and the strongest stored quiz score.
        $duplicateGroups = DB::table('learning_progress')
            ->select('user_id', 'learning_module_id')
            ->whereNotNull('user_id')
            ->whereNotNull('learning_module_id')
            ->groupBy('user_id', 'learning_module_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::table('learning_progress')
                ->where('user_id', $group->user_id)
                ->where('learning_module_id', $group->learning_module_id)
                ->orderByDesc('progress_percentage')
                ->orderByDesc('quiz_score')
                ->orderBy('id')
                ->get();
            $keeper = $rows->first();

            if ($keeper === null) {
                continue;
            }

            $maxProgress = $rows->max(fn ($row): int => (int) ($row->progress_percentage ?? 0));
            $maxQuizScore = $rows->max(fn ($row): ?int => $row->quiz_score === null ? null : (int) $row->quiz_score);
            DB::table('learning_progress')
                ->where('id', $keeper->id)
                ->update([
                    'progress_percentage' => max(0, min(100, (int) $maxProgress)),
                    'status' => (int) $maxProgress >= 100 ? 'completed' : ((int) $maxProgress > 0 ? 'in_progress' : 'enrolled'),
                    'quiz_score' => $maxQuizScore,
                    'updated_at' => now(),
                ]);

            DB::table('learning_progress')
                ->where('user_id', $group->user_id)
                ->where('learning_module_id', $group->learning_module_id)
                ->where('id', '!=', $keeper->id)
                ->delete();
        }

        Schema::table('learning_progress', function (Blueprint $table): void {
            $table->unique(['user_id', 'learning_module_id'], 'learning_progress_user_module_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('learning_progress')) {
            Schema::table('learning_progress', function (Blueprint $table): void {
                $table->dropUnique('learning_progress_user_module_unique');
            });
        }
    }
};
