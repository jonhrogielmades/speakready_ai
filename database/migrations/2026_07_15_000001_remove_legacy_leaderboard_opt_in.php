<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('profiles', 'leaderboard_opt_in')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->dropColumn('leaderboard_opt_in');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('profiles', 'leaderboard_opt_in')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->boolean('leaderboard_opt_in')->default(false);
            });
        }
    }
};
