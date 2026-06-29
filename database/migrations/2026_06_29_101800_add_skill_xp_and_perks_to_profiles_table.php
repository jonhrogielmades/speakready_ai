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
        Schema::table('profiles', function (Blueprint $table) {
            $table->integer('leadership_xp')->default(0)->after('experience_points');
            $table->integer('communication_xp')->default(0)->after('leadership_xp');
            $table->integer('technical_xp')->default(0)->after('communication_xp');
            $table->integer('problem_solving_xp')->default(0)->after('technical_xp');
            $table->json('unlocked_perks')->nullable()->after('badges_earned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'leadership_xp',
                'communication_xp',
                'technical_xp',
                'problem_solving_xp',
                'unlocked_perks'
            ]);
        });
    }
};
