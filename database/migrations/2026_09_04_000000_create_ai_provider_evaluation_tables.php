<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_provider_evaluation_runs')) {
            Schema::create('ai_provider_evaluation_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('benchmark_version')->default('panelist-evidence-v1');
                $table->string('status')->default('completed');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('provider_count')->default(0);
                $table->unsignedInteger('case_count')->default(0);
                $table->json('summary')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_provider_evaluation_results')) {
            Schema::create('ai_provider_evaluation_results', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('run_id')
                    ->constrained('ai_provider_evaluation_runs')
                    ->cascadeOnDelete();
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->string('provider_key');
                $table->string('provider_name');
                $table->string('task_type');
                $table->string('case_key');
                $table->string('status')->default('failed');
                $table->integer('response_time_ms')->nullable();
                $table->unsignedTinyInteger('quality_score')->default(0);
                $table->unsignedTinyInteger('reliability_score')->default(0);
                $table->unsignedTinyInteger('schema_score')->default(0);
                $table->unsignedTinyInteger('accuracy_score')->default(0);
                $table->unsignedTinyInteger('safety_score')->default(0);
                $table->text('prompt_excerpt')->nullable();
                $table->text('output_excerpt')->nullable();
                $table->json('evidence')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['provider_key', 'task_type']);
                $table->index(['run_id', 'provider_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_evaluation_results');
        Schema::dropIfExists('ai_provider_evaluation_runs');
    }
};
