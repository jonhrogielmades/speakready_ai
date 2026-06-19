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
        if (!Schema::hasColumn('categories', 'status')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('icon')->nullable();
                $table->string('status')->default('active'); // active, inactive
                $table->boolean('is_featured')->default(false);
                $table->integer('sort_order')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'status', 'is_featured', 'sort_order']);
        });
    }
};
