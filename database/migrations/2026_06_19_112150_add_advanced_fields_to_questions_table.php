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
        if (!Schema::hasColumn('questions', 'status')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('status')->default('active'); // active, inactive
                $table->text('expected_guide')->nullable();
                $table->json('mapped_skills')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['status', 'expected_guide', 'mapped_skills']);
        });
    }
};
