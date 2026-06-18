<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_sessions', 'interview_focus')) {
                $table->string('interview_focus')->nullable()->after('response_mode');
            }
            if (!Schema::hasColumn('interview_sessions', 'time_limit')) {
                $table->integer('time_limit')->default(0)->after('interview_focus');
            }
            if (!Schema::hasColumn('interview_sessions', 'question_types')) {
                $table->string('question_types')->nullable()->after('time_limit');
            }
            if (!Schema::hasColumn('interview_sessions', 'ai_assistance_level')) {
                $table->string('ai_assistance_level')->default('standard')->after('question_types');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['interview_focus', 'time_limit', 'question_types', 'ai_assistance_level']);
        });
    }
};
