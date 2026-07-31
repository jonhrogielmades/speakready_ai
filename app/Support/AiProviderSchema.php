<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AiProviderSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredTables()) {
            return;
        }

        if ($createIfMissing) {
            self::createMissingTables();
        }

        if (Schema::hasTable('ai_providers')) {
            self::ensureProviderColumns();
        }

        if (Schema::hasTable('ai_provider_logs')) {
            self::ensureLogColumns();
        }

        self::$checked = true;
    }

    public static function hasRequiredTables(): bool
    {
        return Schema::hasTable('ai_providers')
            && Schema::hasTable('ai_provider_logs')
            && self::missingProviderColumns() === []
            && self::missingLogColumns() === [];
    }

    private static function createMissingTables(): void
    {
        if (! Schema::hasTable('ai_providers')) {
            Schema::create('ai_providers', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('api_endpoint')->nullable();
                $table->text('api_key')->nullable();
                $table->string('status')->default('inactive');
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_fallback')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_provider_logs')) {
            Schema::create('ai_provider_logs', function (Blueprint $table): void {
                $table->id();
                self::logColumns($table);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_prompts')) {
            Schema::create('ai_prompts', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('module')->unique();
                $table->text('prompt_text');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_settings')) {
            Schema::create('ai_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    private static function ensureProviderColumns(): void
    {
        $missing = self::missingProviderColumns();

        if ($missing === []) {
            return;
        }

        Schema::table('ai_providers', function (Blueprint $table) use ($missing): void {
            if (in_array('name', $missing, true)) {
                $table->string('name')->nullable();
            }
            if (in_array('api_endpoint', $missing, true)) {
                $table->string('api_endpoint')->nullable();
            }
            if (in_array('api_key', $missing, true)) {
                $table->text('api_key')->nullable();
            }
            if (in_array('status', $missing, true)) {
                $table->string('status')->default('inactive');
            }
            if (in_array('is_primary', $missing, true)) {
                $table->boolean('is_primary')->default(false);
            }
            if (in_array('is_fallback', $missing, true)) {
                $table->boolean('is_fallback')->default(false);
            }
            if (in_array('created_at', $missing, true)) {
                $table->timestamp('created_at')->nullable();
            }
            if (in_array('updated_at', $missing, true)) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureLogColumns(): void
    {
        $missing = self::missingLogColumns();

        if ($missing === []) {
            return;
        }

        Schema::table('ai_provider_logs', function (Blueprint $table) use ($missing): void {
            if (in_array('provider_id', $missing, true)) {
                $table->unsignedBigInteger('provider_id')->nullable();
            }
            if (in_array('module', $missing, true)) {
                $table->string('module')->nullable();
            }
            if (in_array('endpoint', $missing, true)) {
                $table->string('endpoint')->nullable();
            }
            if (in_array('response_time_ms', $missing, true)) {
                $table->integer('response_time_ms')->nullable();
            }
            if (in_array('tokens_used', $missing, true)) {
                $table->integer('tokens_used')->nullable();
            }
            if (in_array('cost', $missing, true)) {
                $table->decimal('cost', 10, 4)->nullable();
            }
            if (in_array('status', $missing, true)) {
                $table->string('status')->nullable();
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

    private static function logColumns(Blueprint $table): void
    {
        $table->unsignedBigInteger('provider_id')->nullable();
        $table->string('module')->nullable();
        $table->string('endpoint')->nullable();
        $table->integer('response_time_ms')->nullable();
        $table->integer('tokens_used')->nullable();
        $table->decimal('cost', 10, 4)->nullable();
        $table->string('status')->nullable();
        $table->text('error_message')->nullable();
    }

    /**
     * @return array<int, string>
     */
    private static function missingProviderColumns(): array
    {
        if (! Schema::hasTable('ai_providers')) {
            return ['ai_providers'];
        }

        return self::missingColumns('ai_providers', [
            'name',
            'api_endpoint',
            'api_key',
            'status',
            'is_primary',
            'is_fallback',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private static function missingLogColumns(): array
    {
        if (! Schema::hasTable('ai_provider_logs')) {
            return ['ai_provider_logs'];
        }

        return self::missingColumns('ai_provider_logs', [
            'provider_id',
            'module',
            'endpoint',
            'response_time_ms',
            'tokens_used',
            'cost',
            'status',
            'error_message',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * @param array<int, string> $columns
     * @return array<int, string>
     */
    private static function missingColumns(string $table, array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn($table, $column)
        ));
    }
}
