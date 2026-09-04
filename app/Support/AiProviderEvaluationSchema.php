<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AiProviderEvaluationSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false): void
    {
        if (! $force && self::$checked && self::hasRequiredTables()) {
            return;
        }

        self::createMissingTables();

        if (Schema::hasTable('ai_provider_evaluation_runs')) {
            self::ensureRunColumns();
        }

        if (Schema::hasTable('ai_provider_evaluation_results')) {
            self::ensureResultColumns();
        }

        self::$checked = true;
    }

    public static function hasRequiredTables(): bool
    {
        return Schema::hasTable('ai_provider_evaluation_runs')
            && Schema::hasTable('ai_provider_evaluation_results')
            && self::missingRunColumns() === []
            && self::missingResultColumns() === [];
    }

    private static function createMissingTables(): void
    {
        if (! Schema::hasTable('ai_provider_evaluation_runs')) {
            Schema::create('ai_provider_evaluation_runs', function (Blueprint $table): void {
                $table->id();
                self::runColumns($table);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_provider_evaluation_results')) {
            Schema::create('ai_provider_evaluation_results', function (Blueprint $table): void {
                $table->id();
                self::resultColumns($table);
                $table->timestamps();
            });
        }
    }

    private static function ensureRunColumns(): void
    {
        $missing = self::missingRunColumns();

        if ($missing === []) {
            return;
        }

        Schema::table('ai_provider_evaluation_runs', function (Blueprint $table) use ($missing): void {
            if (in_array('benchmark_version', $missing, true)) {
                $table->string('benchmark_version')->default('panelist-evidence-v1');
            }
            if (in_array('status', $missing, true)) {
                $table->string('status')->default('completed');
            }
            if (in_array('started_at', $missing, true)) {
                $table->timestamp('started_at')->nullable();
            }
            if (in_array('completed_at', $missing, true)) {
                $table->timestamp('completed_at')->nullable();
            }
            if (in_array('provider_count', $missing, true)) {
                $table->unsignedInteger('provider_count')->default(0);
            }
            if (in_array('case_count', $missing, true)) {
                $table->unsignedInteger('case_count')->default(0);
            }
            if (in_array('summary', $missing, true)) {
                $table->json('summary')->nullable();
            }
            if (in_array('created_by', $missing, true)) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (in_array('created_at', $missing, true)) {
                $table->timestamp('created_at')->nullable();
            }
            if (in_array('updated_at', $missing, true)) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureResultColumns(): void
    {
        $missing = self::missingResultColumns();

        if ($missing === []) {
            return;
        }

        Schema::table('ai_provider_evaluation_results', function (Blueprint $table) use ($missing): void {
            if (in_array('run_id', $missing, true)) {
                $table->unsignedBigInteger('run_id')->nullable();
            }
            if (in_array('provider_id', $missing, true)) {
                $table->unsignedBigInteger('provider_id')->nullable();
            }
            if (in_array('provider_key', $missing, true)) {
                $table->string('provider_key')->nullable();
            }
            if (in_array('provider_name', $missing, true)) {
                $table->string('provider_name')->nullable();
            }
            if (in_array('task_type', $missing, true)) {
                $table->string('task_type')->nullable();
            }
            if (in_array('case_key', $missing, true)) {
                $table->string('case_key')->nullable();
            }
            if (in_array('status', $missing, true)) {
                $table->string('status')->default('failed');
            }
            if (in_array('response_time_ms', $missing, true)) {
                $table->integer('response_time_ms')->nullable();
            }
            if (in_array('quality_score', $missing, true)) {
                $table->unsignedTinyInteger('quality_score')->default(0);
            }
            if (in_array('reliability_score', $missing, true)) {
                $table->unsignedTinyInteger('reliability_score')->default(0);
            }
            if (in_array('schema_score', $missing, true)) {
                $table->unsignedTinyInteger('schema_score')->default(0);
            }
            if (in_array('accuracy_score', $missing, true)) {
                $table->unsignedTinyInteger('accuracy_score')->default(0);
            }
            if (in_array('safety_score', $missing, true)) {
                $table->unsignedTinyInteger('safety_score')->default(0);
            }
            if (in_array('prompt_excerpt', $missing, true)) {
                $table->text('prompt_excerpt')->nullable();
            }
            if (in_array('output_excerpt', $missing, true)) {
                $table->text('output_excerpt')->nullable();
            }
            if (in_array('evidence', $missing, true)) {
                $table->json('evidence')->nullable();
            }
            if (in_array('error_message', $missing, true)) {
                $table->text('error_message')->nullable();
            }
            if (in_array('created_at', $missing, true)) {
                $table->timestamp('created_at')->nullable();
            }
            if (in_array('updated_at', $missing, true)) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function runColumns(Blueprint $table): void
    {
        $table->string('benchmark_version')->default('panelist-evidence-v1');
        $table->string('status')->default('completed');
        $table->timestamp('started_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->unsignedInteger('provider_count')->default(0);
        $table->unsignedInteger('case_count')->default(0);
        $table->json('summary')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
    }

    private static function resultColumns(Blueprint $table): void
    {
        $table->unsignedBigInteger('run_id');
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
    }

    private static function missingRunColumns(): array
    {
        if (! Schema::hasTable('ai_provider_evaluation_runs')) {
            return ['ai_provider_evaluation_runs'];
        }

        return self::missingColumns('ai_provider_evaluation_runs', [
            'benchmark_version',
            'status',
            'started_at',
            'completed_at',
            'provider_count',
            'case_count',
            'summary',
            'created_by',
            'created_at',
            'updated_at',
        ]);
    }

    private static function missingResultColumns(): array
    {
        if (! Schema::hasTable('ai_provider_evaluation_results')) {
            return ['ai_provider_evaluation_results'];
        }

        return self::missingColumns('ai_provider_evaluation_results', [
            'run_id',
            'provider_id',
            'provider_key',
            'provider_name',
            'task_type',
            'case_key',
            'status',
            'response_time_ms',
            'quality_score',
            'reliability_score',
            'schema_score',
            'accuracy_score',
            'safety_score',
            'prompt_excerpt',
            'output_excerpt',
            'evidence',
            'error_message',
            'created_at',
            'updated_at',
        ]);
    }

    private static function missingColumns(string $table, array $columns): array
    {
        $existing = array_flip(Schema::getColumnListing($table));

        return array_values(array_filter($columns, fn (string $column): bool => ! isset($existing[$column])));
    }
}
