<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChatbotSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false): void
    {
        if (! $force && self::$checked && self::hasRequiredTables()) {
            return;
        }

        self::ensureConversationsTable();
        self::ensureMessagesTable();

        self::$checked = true;
    }

    public static function hasRequiredTables(): bool
    {
        return Schema::hasTable('chatbot_conversations')
            && Schema::hasTable('chatbot_messages')
            && self::missingColumns('chatbot_conversations', self::conversationColumns()) === []
            && self::missingColumns('chatbot_messages', self::messageColumns()) === [];
    }

    private static function ensureConversationsTable(): void
    {
        if (! Schema::hasTable('chatbot_conversations')) {
            Schema::create('chatbot_conversations', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users');
                $table->string('title')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('chatbot_conversations', self::conversationColumns());
        if ($missing === []) {
            return;
        }

        Schema::table('chatbot_conversations', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'user_id')) {
                self::foreignId($table, 'user_id', 'users', true);
            }
            if (self::isMissing($missing, 'title')) {
                $table->string('title')->nullable();
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureMessagesTable(): void
    {
        if (! Schema::hasTable('chatbot_messages')) {
            Schema::create('chatbot_messages', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'chatbot_conversation_id', 'chatbot_conversations');
                $table->string('role');
                $table->text('content');
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('chatbot_messages', self::messageColumns());
        if ($missing === []) {
            return;
        }

        Schema::table('chatbot_messages', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'chatbot_conversation_id')) {
                self::foreignId($table, 'chatbot_conversation_id', 'chatbot_conversations', true);
            }
            if (self::isMissing($missing, 'role')) {
                $table->string('role')->default('user');
            }
            if (self::isMissing($missing, 'content')) {
                $table->text('content')->nullable();
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function foreignId(Blueprint $table, string $column, string $relatedTable, bool $nullable = false): void
    {
        if (! Schema::hasTable($relatedTable)) {
            $definition = $table->unsignedBigInteger($column);

            if ($nullable) {
                $definition->nullable();
            }

            return;
        }

        $definition = $table->foreignId($column);

        if ($nullable) {
            $definition->nullable();
        }

        $definition->constrained($relatedTable)->cascadeOnDelete();
    }

    /**
     * @return array<int, string>
     */
    private static function conversationColumns(): array
    {
        return [
            'user_id',
            'title',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function messageColumns(): array
    {
        return [
            'chatbot_conversation_id',
            'role',
            'content',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private static function missingColumns(string $table, array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn($table, $column)
        ));
    }

    /**
     * @param  array<int, string>  $missing
     */
    private static function isMissing(array $missing, string $column): bool
    {
        return in_array($column, $missing, true);
    }
}
