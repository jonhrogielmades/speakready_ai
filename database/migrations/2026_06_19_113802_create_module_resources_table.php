<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_module_id')->constrained('learning_modules')->onDelete('cascade');
            $table->string('title');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // pdf, docx, pptx
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_resources');
    }
};
