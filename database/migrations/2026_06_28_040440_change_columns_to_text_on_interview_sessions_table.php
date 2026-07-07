<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->text('interview_focus')->nullable()->change();
            $table->text('company_persona')->nullable()->change();
            $table->text('target_position')->nullable()->change();
            $table->text('job_description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->string('interview_focus', 255)->nullable()->change();
            $table->string('company_persona', 255)->nullable()->change();
            $table->string('target_position', 255)->nullable()->change();
            $table->string('job_description', 255)->nullable()->change();
        });
    }
};
