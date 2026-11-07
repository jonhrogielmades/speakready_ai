<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_sessions') || Schema::hasColumn('interview_sessions', 'job_category')) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->string('job_category', 100)->nullable()->after('target_position');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_sessions') || ! Schema::hasColumn('interview_sessions', 'job_category')) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropColumn('job_category');
        });
    }
};
