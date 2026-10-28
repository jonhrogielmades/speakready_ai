<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'email')
            || ! Schema::hasColumn('users', 'auth_method')) {
            return;
        }

        $emailUniqueIndex = $this->findIndex('users', ['email'], true);

        if ($emailUniqueIndex) {
            Schema::table('users', function (Blueprint $table) use ($emailUniqueIndex): void {
                $table->dropUnique($emailUniqueIndex);
            });
        }

        if (! $this->findIndex('users', ['email', 'auth_method'], true)) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique(['email', 'auth_method'], 'users_email_auth_method_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return;
        }

        $sharedEmailIndex = $this->findIndex('users', ['email', 'auth_method'], true);

        if ($sharedEmailIndex) {
            Schema::table('users', function (Blueprint $table) use ($sharedEmailIndex): void {
                $table->dropUnique($sharedEmailIndex);
            });
        }

        if (! $this->findIndex('users', ['email'], true)) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('email', 'users_email_unique');
            });
        }
    }

    private function findIndex(string $table, array $columns, bool $unique): ?string
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            return null;
        }

        try {
            foreach (Schema::getIndexes($table) as $index) {
                if (($index['unique'] ?? false) === $unique
                    && array_values($index['columns'] ?? []) === $columns) {
                    return $index['name'] ?? null;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
};
