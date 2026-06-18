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
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->integer('num_questions')->default(5)->after('difficulty');
            $table->string('coach_focus_mode')->default('balanced')->after('num_questions');
            $table->string('response_mode')->default('text')->after('coach_focus_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['num_questions', 'coach_focus_mode', 'response_mode']);
        });
    }
};
