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
                $table->string('username', 64)->nullable()->after('name');
            });
        }

        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('email')->nullable()->change();
            });
        }

        $this->backfillUsernames();

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

        Schema::table('users', function (Blueprint $table): void {
            if ($this->hasIndex('users', 'users_username_unique')) {
                $table->dropUnique('users_username_unique');
            }

            $table->dropColumn('username');
        });
    }

    private function backfillUsernames(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            return;
        }

        $taken = DB::table('users')
            ->whereNotNull('username')
            ->pluck('username')
            ->map(fn ($username): string => Str::lower((string) $username))
            ->all();
        $taken = array_fill_keys($taken, true);

        DB::table('users')
            ->select(['id', 'name', 'email', 'username'])
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$taken): void {
                foreach ($users as $user) {
                    if (filled($user->username)) {
                        continue;
                    }

                    $base = $this->usernameBase($user->email ?: $user->name ?: 'user-'.$user->id);
                    $candidate = $base;
                    $suffix = 2;

                    while (isset($taken[$candidate])) {
                        $candidate = Str::limit($base, 55, '').'-'.$suffix;
                        $suffix++;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $candidate]);

                    $taken[$candidate] = true;
                }
            });
    }

    private function usernameBase(string $seed): string
    {
        $seed = Str::before($seed, '@');
        $seed = Str::ascii(Str::lower($seed));
        $seed = preg_replace('/[^a-z0-9._-]+/', '-', $seed) ?: '';
        $seed = trim($seed, '.-_');

        if (strlen($seed) < 3) {
            $seed = 'user-'.$seed;
        }

        return Str::limit($seed, 60, '');
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
        } catch (Throwable) {
            return false;
        }

        return false;
    }
};
