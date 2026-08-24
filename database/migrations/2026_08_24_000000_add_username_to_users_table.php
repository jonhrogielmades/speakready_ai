<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username', 30)->nullable()->after('name');
            });
        }

        DB::table('users')
            ->whereNull('username')
            ->orderBy('id')
            ->select(['id', 'name', 'email'])
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $this->uniqueUsernameFor($user)]);
                }
            });

        if (! $this->hasIndex('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('username', 'users_username_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'username')) {
            return;
        }

        if ($this->hasIndex('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_username_unique');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('username');
        });
    }

    private function uniqueUsernameFor(object $user): string
    {
        $source = trim((string) ($user->email ?: $user->name ?: ('user' . $user->id)));
        $baseSource = Str::contains($source, '@') ? Str::before($source, '@') : $source;
        $base = $this->normalizeUsername($baseSource) ?: ('user' . $user->id);
        $base = trim(Str::limit($base, 24, ''), '_') ?: ('user' . $user->id);
        $candidate = $base;
        $suffix = 2;

        while ($this->usernameExists($candidate)) {
            $suffixText = (string) $suffix;
            $prefixLength = max(1, 30 - strlen($suffixText));
            $prefix = trim(Str::limit($base, $prefixLength, ''), '_') ?: 'user';
            $candidate = $prefix . $suffixText;
            $suffix++;
        }

        return $candidate;
    }

    private function normalizeUsername(string $username): string
    {
        $username = Str::lower(Str::ascii(trim($username)));
        $username = preg_replace('/[^a-z0-9_]+/', '_', $username) ?? '';

        return trim($username, '_');
    }

    private function usernameExists(string $username): bool
    {
        return DB::table('users')
            ->whereNotNull('username')
            ->whereRaw('LOWER(username) = ?', [Str::lower($username)])
            ->exists();
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
