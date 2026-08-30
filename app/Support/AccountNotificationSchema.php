<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountNotificationSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false): void
    {
        if (! $force && self::$checked && self::hasRequiredTables()) {
            return;
        }

        self::ensureUserColumns();
        self::ensureNotificationsTable();
        self::ensureActivityLogsTable();

        self::$checked = true;
    }

    public static function ensureUserColumns(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $missing = self::missingColumns('users', self::userColumns());
        if ($missing !== []) {
            Schema::table('users', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'is_admin')) {
                    $table->boolean('is_admin')->default(false);
                }
                if (self::isMissing($missing, 'google_id')) {
                    $table->string('google_id')->nullable();
                }
                if (self::isMissing($missing, 'status')) {
                    $table->string('status')->default('active');
                }
                if (self::isMissing($missing, 'reactivation_requested_at')) {
                    $table->timestamp('reactivation_requested_at')->nullable();
                }
                if (self::isMissing($missing, 'profile_photo_path')) {
                    $table->longText('profile_photo_path')->nullable();
                }
                if (self::isMissing($missing, 'target_position')) {
                    $table->string('target_position')->nullable();
                }
                if (self::isMissing($missing, 'preferred_language')) {
                    $table->string('preferred_language', 12)->nullable();
                }
                if (self::isMissing($missing, 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable();
                }
            });
        }

        self::ensureProfilePhotoPathCapacity();

        if (Schema::hasColumn('users', 'is_admin')) {
            DB::table('users')->whereNull('is_admin')->update(['is_admin' => false]);
        }

        if (Schema::hasColumn('users', 'status')) {
            DB::table('users')->whereNull('status')->update(['status' => 'active']);
        }
    }

    public static function hasRequiredTables(): bool
    {
        return self::hasRequiredUserColumns()
            && self::hasRequiredNotificationsTable()
            && self::hasRequiredActivityLogsTable();
    }

    public static function hasRequiredUserColumns(): bool
    {
        return ! Schema::hasTable('users')
            || (
                self::missingColumns('users', self::userColumns()) === []
                && self::profilePhotoPathHasRequiredCapacity()
            );
    }

    public static function hasRequiredNotificationsTable(): bool
    {
        return Schema::hasTable('notifications')
            && self::missingColumns('notifications', self::notificationColumns()) === [];
    }

    public static function hasRequiredActivityLogsTable(): bool
    {
        return Schema::hasTable('activity_logs')
            && self::missingColumns('activity_logs', self::activityLogColumns()) === [];
    }

    private static function ensureNotificationsTable(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->longText('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('notifications', self::notificationColumns());
        if ($missing === []) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'id')) {
                $table->uuid('id')->nullable();
            }
            if (self::isMissing($missing, 'type')) {
                $table->string('type')->nullable();
            }
            if (self::isMissing($missing, 'notifiable_type')) {
                $table->string('notifiable_type')->nullable();
            }
            if (self::isMissing($missing, 'notifiable_id')) {
                $table->unsignedBigInteger('notifiable_id')->nullable();
            }
            if (self::isMissing($missing, 'data')) {
                $table->longText('data')->nullable();
            }
            if (self::isMissing($missing, 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
            if (self::isMissing($missing, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (self::isMissing($missing, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private static function ensureActivityLogsTable(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table): void {
                $table->id();
                self::foreignId($table, 'user_id', 'users');
                $table->string('action');
                $table->text('description')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        $missing = self::missingColumns('activity_logs', self::activityLogColumns());
        if ($missing === []) {
            return;
        }

        Schema::table('activity_logs', function (Blueprint $table) use ($missing): void {
            if (self::isMissing($missing, 'user_id')) {
                self::foreignId($table, 'user_id', 'users', true);
            }
            if (self::isMissing($missing, 'action')) {
                $table->string('action')->default('activity');
            }
            if (self::isMissing($missing, 'description')) {
                $table->text('description')->nullable();
            }
            if (self::isMissing($missing, 'ip_address')) {
                $table->string('ip_address')->nullable();
            }
            if (self::isMissing($missing, 'read_at')) {
                $table->timestamp('read_at')->nullable();
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
        if (Schema::hasTable($relatedTable)) {
            $definition = $table->foreignId($column);

            if ($nullable) {
                $definition->nullable();
            }

            $definition->constrained($relatedTable);
            $nullable ? $definition->nullOnDelete() : $definition->cascadeOnDelete();

            return;
        }

        $definition = $table->unsignedBigInteger($column);

        if ($nullable) {
            $definition->nullable();
        }
    }

    private static function ensureProfilePhotoPathCapacity(): void
    {
        if (! Schema::hasColumn('users', 'profile_photo_path') || self::profilePhotoPathHasRequiredCapacity()) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        try {
            match ($driver) {
                'pgsql' => DB::statement('ALTER TABLE users ALTER COLUMN profile_photo_path TYPE TEXT'),
                'mysql', 'mariadb' => DB::statement('ALTER TABLE users MODIFY profile_photo_path LONGTEXT NULL'),
                'sqlsrv' => DB::statement('ALTER TABLE users ALTER COLUMN profile_photo_path NVARCHAR(MAX) NULL'),
                default => null,
            };
        } catch (\Throwable) {
            // Some lightweight development drivers cannot alter column types in place.
        }
    }

    private static function profilePhotoPathHasRequiredCapacity(): bool
    {
        if (! Schema::hasColumn('users', 'profile_photo_path')) {
            return false;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return true;
        }

        try {
            $type = strtolower((string) Schema::getColumnType('users', 'profile_photo_path', true));
        } catch (\Throwable) {
            return true;
        }

        return str_contains($type, 'text')
            || str_contains($type, 'longtext')
            || str_contains($type, 'nvarchar(max)')
            || str_contains($type, 'varchar(max)');
    }

    /**
     * @return array<int, string>
     */
    private static function userColumns(): array
    {
        return [
            'is_admin',
            'google_id',
            'status',
            'reactivation_requested_at',
            'profile_photo_path',
            'target_position',
            'preferred_language',
            'deleted_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function notificationColumns(): array
    {
        return [
            'id',
            'type',
            'notifiable_type',
            'notifiable_id',
            'data',
            'read_at',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function activityLogColumns(): array
    {
        return [
            'user_id',
            'action',
            'description',
            'ip_address',
            'read_at',
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
