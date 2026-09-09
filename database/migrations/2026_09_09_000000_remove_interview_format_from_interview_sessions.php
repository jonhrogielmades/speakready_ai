<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_sessions') || ! Schema::hasColumn('interview_sessions', 'interview_format')) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropColumn('interview_format');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_sessions') || Schema::hasColumn('interview_sessions', 'interview_format')) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->string('interview_format')->default('standard');
        });
    }
};
