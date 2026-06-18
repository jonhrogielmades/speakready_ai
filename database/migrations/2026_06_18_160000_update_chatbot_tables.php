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
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_conversations', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('chatbot_conversations', 'title')) {
                $table->string('title')->nullable();
            }
        });

        Schema::table('chatbot_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_messages', 'chatbot_conversation_id')) {
                $table->foreignId('chatbot_conversation_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('chatbot_messages', 'role')) {
                $table->string('role'); // 'user' or 'ai'
            }
            if (!Schema::hasColumn('chatbot_messages', 'content')) {
                $table->text('content');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->dropForeign(['chatbot_conversation_id']);
            $table->dropColumn(['chatbot_conversation_id', 'role', 'content']);
        });

        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'title']);
        });
    }
};
