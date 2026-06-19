<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_progress', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('learning_module_id')->nullable()->constrained('learning_modules')->onDelete('cascade');
            $table->string('status')->default('enrolled'); // enrolled, completed
            $table->integer('progress_percentage')->default(0);
            $table->integer('quiz_score')->nullable();
            $table->decimal('learning_hours', 8, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('learning_progress', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['learning_module_id']);
            $table->dropColumn(['user_id', 'learning_module_id', 'status', 'progress_percentage', 'quiz_score', 'learning_hours']);
        });
    }
};
