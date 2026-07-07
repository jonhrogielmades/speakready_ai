<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = $this->levelsTable();
        if (!$tableName || !Schema::hasColumn($tableName, 'category_id')) {
            return;
        }

        foreach (["{$tableName}_level_number_unique", 'arena_levels_level_number_unique'] as $indexName) {
            try {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
                break;
            } catch (\Throwable $e) {
                // Some databases keep the old index name after a table rename.
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $table->unique(['category_id', 'level_number'], "{$tableName}_category_id_level_number_unique");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = $this->levelsTable();
        if (!$tableName) {
            return;
        }

        foreach (["{$tableName}_category_id_level_number_unique", 'arena_levels_category_id_level_number_unique'] as $indexName) {
            try {
                Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
                break;
            } catch (\Throwable $e) {
                // See the matching up() note about renamed index names.
            }
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->unique('level_number');
        });
    }

    private function levelsTable(): ?string
    {
        if (Schema::hasTable('game_levels')) {
            return 'game_levels';
        }

        if (Schema::hasTable('arena_levels')) {
            return 'arena_levels';
        }

        return null;
    }
};
