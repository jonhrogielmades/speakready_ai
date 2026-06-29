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
        Schema::table('interview_answers', function (Blueprint $table) {
            $table->integer('pause_count')->default(0)->after('filler_words_count');
        });

        Schema::table('scores', function (Blueprint $table) {
            $table->integer('ats_match_score')->default(0)->after('overall_readiness_score');
            $table->integer('star_method_score')->default(0)->after('ats_match_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_answers', function (Blueprint $table) {
            $table->dropColumn('pause_count');
        });

        Schema::table('scores', function (Blueprint $table) {
            $table->dropColumn(['ats_match_score', 'star_method_score']);
        });
    }
};
