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
        if (! Schema::hasTable('voice_sessions')) {
            return;
        }

        Schema::table('voice_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('voice_sessions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            }
            if (! Schema::hasColumn('voice_sessions', 'speaking_pace')) {
                $table->integer('speaking_pace')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'clarity_score')) {
                $table->integer('clarity_score')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'confidence_score')) {
                $table->integer('confidence_score')->nullable();
            }
            if (! Schema::hasColumn('voice_sessions', 'filler_words')) {
                $table->integer('filler_words')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('voice_sessions')) {
            return;
        }

        Schema::table('voice_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('voice_sessions', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('voice_sessions', 'speaking_pace') ? 'speaking_pace' : null,
                Schema::hasColumn('voice_sessions', 'clarity_score') ? 'clarity_score' : null,
                Schema::hasColumn('voice_sessions', 'confidence_score') ? 'confidence_score' : null,
                Schema::hasColumn('voice_sessions', 'filler_words') ? 'filler_words' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
