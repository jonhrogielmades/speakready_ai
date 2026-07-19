<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interview_answers')) {
            Schema::table('interview_answers', function (Blueprint $table): void {
                if (! Schema::hasColumn('interview_answers', 'observation_data')) {
                    $table->json('observation_data')->nullable();
                }
                if (! Schema::hasColumn('interview_answers', 'delivery_transcript')) {
                    $table->text('delivery_transcript')->nullable();
                }
                if (! Schema::hasColumn('interview_answers', 'coaching_feedback')) {
                    $table->json('coaching_feedback')->nullable();
                }
            });
        }

        if (Schema::hasTable('feedback') && ! Schema::hasColumn('feedback', 'coaching_summary')) {
            Schema::table('feedback', function (Blueprint $table): void {
                $table->json('coaching_summary')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interview_answers')) {
            Schema::table('interview_answers', function (Blueprint $table): void {
                $columns = array_values(array_filter(
                    ['observation_data', 'delivery_transcript', 'coaching_feedback'],
                    fn (string $column): bool => Schema::hasColumn('interview_answers', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('feedback') && Schema::hasColumn('feedback', 'coaching_summary')) {
            Schema::table('feedback', function (Blueprint $table): void {
                $table->dropColumn('coaching_summary');
            });
        }
    }
};
