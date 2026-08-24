<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return;
        }

        DB::table('users')
            ->whereNull('email')
            ->orderBy('id')
            ->select(['id', 'username'])
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $username = preg_replace('/[^a-z0-9_]+/i', '_', (string) ($user->username ?: 'user'));

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['email' => strtolower(trim($username, '_') ?: 'user') . $user->id . '@speakready.local']);
                }
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
        });
    }
};
