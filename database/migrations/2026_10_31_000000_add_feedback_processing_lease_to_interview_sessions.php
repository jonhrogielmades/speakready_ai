<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_sessions')) {
            return;
        }

        if (! Schema::hasColumn('interview_sessions', 'feedback_processing_token')) {
            Schema::table('interview_sessions', function (Blueprint $table): void {
                $table->string('feedback_processing_token', 64)->nullable();
            });
        }

        if (! Schema::hasColumn('interview_sessions', 'feedback_processing_started_at')) {
            Schema::table('interview_sessions', function (Blueprint $table): void {
                $table->timestamp('feedback_processing_started_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_sessions')) {
            return;
        }

        foreach (['feedback_processing_token', 'feedback_processing_started_at'] as $column) {
            if (Schema::hasColumn('interview_sessions', $column)) {
                Schema::table('interview_sessions', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
