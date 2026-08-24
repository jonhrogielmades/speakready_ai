<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VoiceSessionSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredColumns()) {
            return;
        }

        if (! Schema::hasTable('voice_sessions')) {
            if ($createIfMissing && Schema::hasTable('users')) {
                self::createTable();
            }

            self::$checked = true;

            return;
        }

        $missing = self::missingColumns(self::requiredColumns());

        if ($missing !== []) {
            Schema::table('voice_sessions', function (Blueprint $table) use ($missing): void {
                if (in_array('user_id', $missing, true)) {
                    self::addUserId($table);
                }
                if (in_array('speaking_pace', $missing, true)) {
                    $table->integer('speaking_pace')->nullable();
                }
                if (in_array('clarity_score', $missing, true)) {
                    $table->integer('clarity_score')->nullable();
                }
                if (in_array('confidence_score', $missing, true)) {
                    $table->integer('confidence_score')->nullable();
                }
                if (in_array('filler_words', $missing, true)) {
                    $table->integer('filler_words')->nullable();
                }
                if (in_array('category', $missing, true)) {
                    $table->string('category')->nullable();
                }
                if (in_array('prompt', $missing, true)) {
                    $table->text('prompt')->nullable();
                }
                if (in_array('transcript', $missing, true)) {
                    $table->text('transcript')->nullable();
                }
                if (in_array('ai_feedback_strengths', $missing, true)) {
                    $table->text('ai_feedback_strengths')->nullable();
                }
                if (in_array('ai_feedback_weaknesses', $missing, true)) {
                    $table->text('ai_feedback_weaknesses')->nullable();
                }
                if (in_array('ai_improved_answer', $missing, true)) {
                    $table->text('ai_improved_answer')->nullable();
                }
                if (in_array('pronunciation_analysis', $missing, true)) {
                    $table->json('pronunciation_analysis')->nullable();
                }
                if (in_array('pronunciation_score', $missing, true)) {
                    $table->unsignedTinyInteger('pronunciation_score')->nullable();
                }
                if (in_array('duration_seconds', $missing, true)) {
                    $table->integer('duration_seconds')->nullable();
                }
                if (in_array('wpm', $missing, true)) {
                    $table->integer('wpm')->nullable();
                }
                if (in_array('created_at', $missing, true)) {
                    $table->timestamp('created_at')->nullable();
                }
                if (in_array('updated_at', $missing, true)) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        self::$checked = true;
    }

    public static function hasRequiredColumns(): bool
    {
        return Schema::hasTable('voice_sessions')
            && self::missingColumns(self::requiredColumns()) === [];
    }

    private static function createTable(): void
    {
        Schema::create('voice_sessions', function (Blueprint $table): void {
            $table->id();
            self::addUserId($table);
            $table->integer('speaking_pace')->nullable();
            $table->integer('clarity_score')->nullable();
            $table->integer('confidence_score')->nullable();
            $table->integer('filler_words')->nullable();
            $table->string('category')->nullable();
            $table->text('prompt')->nullable();
            $table->text('transcript')->nullable();
            $table->text('ai_feedback_strengths')->nullable();
            $table->text('ai_feedback_weaknesses')->nullable();
            $table->text('ai_improved_answer')->nullable();
            $table->json('pronunciation_analysis')->nullable();
            $table->unsignedTinyInteger('pronunciation_score')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('wpm')->nullable();
            $table->timestamps();
        });
    }

    private static function requiredColumns(): array
    {
        return [
            'user_id',
            'speaking_pace',
            'clarity_score',
            'confidence_score',
            'filler_words',
            'category',
            'prompt',
            'transcript',
            'ai_feedback_strengths',
            'ai_feedback_weaknesses',
            'ai_improved_answer',
            'pronunciation_analysis',
            'pronunciation_score',
            'duration_seconds',
            'wpm',
            'created_at',
            'updated_at',
        ];
    }

    private static function addUserId(Blueprint $table): void
    {
        if (Schema::hasTable('users')) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            return;
        }

        $table->unsignedBigInteger('user_id')->nullable();
    }

    private static function missingColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn('voice_sessions', $column)
        ));
    }
}
