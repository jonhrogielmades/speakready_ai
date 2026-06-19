<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_modules', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title');
            $table->string('difficulty')->nullable()->after('category');
            $table->string('status')->default('draft')->after('type'); // published, draft, archived
            $table->integer('views')->default(0)->after('status');
            $table->boolean('is_featured')->default(false)->after('views');
            $table->json('mapped_skills')->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('learning_modules', function (Blueprint $table) {
            $table->dropColumn(['category', 'difficulty', 'status', 'views', 'is_featured', 'mapped_skills']);
        });
    }
};
