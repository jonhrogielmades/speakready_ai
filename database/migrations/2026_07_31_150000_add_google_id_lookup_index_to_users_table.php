<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'google_id')) {
            return;
        }

        if ($this->hasIndex('users', 'users_google_id_lookup_index')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->index('google_id', 'users_google_id_lookup_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! $this->hasIndex('users', 'users_google_id_lookup_index')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_google_id_lookup_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            return false;
        }

        try {
            foreach (Schema::getIndexes($table) as $existingIndex) {
                if (($existingIndex['name'] ?? null) === $index) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
};
