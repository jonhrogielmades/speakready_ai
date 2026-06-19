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
        Schema::table('scores', function (Blueprint $table) {
            if (!Schema::hasColumn('scores', 'confidence_score')) {
                $table->integer('confidence_score')->default(0)->after('professionalism_score');
            }
        });

        Schema::table('interview_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_sessions', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('status');
            }
            if (!Schema::hasColumn('interview_sessions', 'flag_reason')) {
                $table->string('flag_reason')->nullable()->after('is_archived');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropColumn('confidence_score');
        });

        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'flag_reason']);
        });
    }
};
