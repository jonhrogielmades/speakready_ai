<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->integer('energy')->default(3)->after('badges_earned');
            $table->timestamp('energy_last_refilled_at')->nullable()->after('energy');
            $table->integer('player_level')->default(1)->after('experience_points');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['energy', 'energy_last_refilled_at', 'player_level']);
        });
    }
};
