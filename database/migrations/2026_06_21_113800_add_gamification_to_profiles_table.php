<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->integer('experience_points')->default(0)->after('readiness_score');
            $table->integer('current_streak')->default(0)->after('experience_points');
            $table->integer('longest_streak')->default(0)->after('current_streak');
            $table->date('last_activity_date')->nullable()->after('longest_streak');
            $table->json('badges_earned')->nullable()->after('last_activity_date');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['experience_points', 'current_streak', 'longest_streak', 'last_activity_date', 'badges_earned']);
        });
    }
};
