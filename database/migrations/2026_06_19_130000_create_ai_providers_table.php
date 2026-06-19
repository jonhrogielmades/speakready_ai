<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_endpoint')->nullable();
            $table->text('api_key')->nullable();
            $table->string('status')->default('inactive'); // active, inactive
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_fallback')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
