<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chatbot_messages') && ! Schema::hasColumn('chatbot_messages', 'quality_metadata')) {
            Schema::table('chatbot_messages', function (Blueprint $table): void {
                $table->json('quality_metadata')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chatbot_messages') && Schema::hasColumn('chatbot_messages', 'quality_metadata')) {
            Schema::table('chatbot_messages', function (Blueprint $table): void {
                $table->dropColumn('quality_metadata');
            });
        }
    }
};
