<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_answer_provider_reviews')) {
            Schema::create('interview_answer_provider_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
                $table->foreignId('interview_answer_id')->unique()->constrained('interview_answers')->cascadeOnDelete();
                $table->string('status', 24)->default('pending');
                $table->string('input_fingerprint', 64)->nullable();
                $table->string('provider_key', 40)->nullable();
                $table->string('processing_token', 64)->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->string('failure_code', 80)->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // The default Laravel name exceeds MariaDB's 64-character limit.
        // An existing table may also be left by an interrupted migration.
        if (! Schema::hasIndex('interview_answer_provider_reviews', 'iapr_session_status_idx')) {
            Schema::table('interview_answer_provider_reviews', function (Blueprint $table): void {
                $table->index(['interview_session_id', 'status'], 'iapr_session_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_answer_provider_reviews');
    }
};
