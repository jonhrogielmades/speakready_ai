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
            $table->integer('eye_contact_score')->default(0)->after('filler_words_count');
            $table->integer('posture_score')->default(0)->after('eye_contact_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_answers', function (Blueprint $table) {
            $table->dropColumn(['eye_contact_score', 'posture_score']);
        });
    }
};
