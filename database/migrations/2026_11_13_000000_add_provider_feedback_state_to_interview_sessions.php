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

        Schema::table('interview_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('interview_sessions', 'feedback_status')) {
                // Null identifies a legacy session whose provider state has not
                // been migrated. New completion requests set this explicitly.
                $table->string('feedback_status', 24)->nullable();
            }
            if (! Schema::hasColumn('interview_sessions', 'feedback_failure_code')) {
                $table->string('feedback_failure_code', 80)->nullable();
            }
            if (! Schema::hasColumn('interview_sessions', 'feedback_attempts')) {
                $table->unsignedInteger('feedback_attempts')->default(0);
            }
            if (! Schema::hasColumn('interview_sessions', 'feedback_started_at')) {
                $table->timestamp('feedback_started_at')->nullable();
            }
            if (! Schema::hasColumn('interview_sessions', 'feedback_completed_at')) {
                $table->timestamp('feedback_completed_at')->nullable();
            }
            if (! Schema::hasColumn('interview_sessions', 'feedback_input_fingerprint')) {
                $table->string('feedback_input_fingerprint', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_sessions')) {
            return;
        }

        $columns = array_values(array_filter([
            'feedback_status',
            'feedback_failure_code',
            'feedback_attempts',
            'feedback_started_at',
            'feedback_completed_at',
            'feedback_input_fingerprint',
        ], fn (string $column): bool => Schema::hasColumn('interview_sessions', $column)));

        if ($columns !== []) {
            Schema::table('interview_sessions', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
