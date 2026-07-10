<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class WipeDataPreserveAdmin extends Command
{
    protected $signature = 'app:wipe-data-preserve-admin
        {--admin-email=admin@speakreadyai.com : Admin email address to keep}
        {--delete-uploads : Delete uploaded files from the public storage disk}
        {--force : Run without an interactive confirmation}';

    protected $description = 'Delete all application data while preserving exactly one admin account.';

    public function handle(): int
    {
        $adminEmail = strtolower(trim((string) $this->option('admin-email')));

        if ($adminEmail === '') {
            $this->error('The --admin-email option is required.');

            return self::FAILURE;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        $admin = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$adminEmail])
            ->first();

        if (!$admin) {
            $this->error("No admin account was found for {$adminEmail}. Run db:seed or pass the correct --admin-email first.");

            return self::FAILURE;
        }

        if (!$admin->is_admin) {
            $this->error("The account {$adminEmail} exists, but is_admin is not enabled.");

            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $this->warn("This will delete all application data in database [{$database}] except {$adminEmail}.");
            if (!$this->confirm('Continue with the wipe?')) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $tables = collect($this->tables($driver))
            ->reject(fn (string $table) => in_array($table, ['migrations', 'users'], true))
            ->values()
            ->all();

        $before = $this->rowCounts(array_merge(['users'], $tables));

        $this->disableForeignKeys($driver);

        try {
            $this->emptyTables($driver, $tables);

            DB::table('users')->where('id', '<>', $admin->id)->delete();
            $this->normalizeAdminAccount((int) $admin->id);

            $this->resetSequence($driver, 'users');
        } finally {
            $this->enableForeignKeys($driver);
        }

        if ($this->option('delete-uploads')) {
            $this->deleteUploads();
        }

        $after = $this->rowCounts(array_merge(['users'], $tables));
        $nonEmptyTables = collect($after)
            ->except('users')
            ->filter(fn (int $count) => $count !== 0)
            ->keys()
            ->all();

        $remainingAdminCount = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$adminEmail])
            ->where('is_admin', true)
            ->count();

        $userCount = (int) DB::table('users')->count();

        $this->table(
            ['Table', 'Before', 'After'],
            collect($after)->map(fn (int $count, string $table) => [
                $table,
                $before[$table] ?? 0,
                $count,
            ])->values()->all()
        );

        if ($userCount !== 1 || $remainingAdminCount !== 1 || $nonEmptyTables !== []) {
            $this->error('Verification failed: the database is not empty except for the admin user.');

            return self::FAILURE;
        }

        $this->info("Verification passed: only {$adminEmail} remains; all other application tables are empty.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function tables(string $driver): array
    {
        return match ($driver) {
            'mysql' => collect(DB::select(
                "SELECT table_name AS name
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_type = 'BASE TABLE'
                ORDER BY table_name"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            'pgsql' => collect(DB::select(
                "SELECT tablename AS name
                FROM pg_tables
                WHERE schemaname = current_schema()
                ORDER BY tablename"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            'sqlite' => collect(DB::select(
                "SELECT name
                FROM sqlite_master
                WHERE type = 'table'
                AND name NOT LIKE 'sqlite_%'
                ORDER BY name"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            'sqlsrv' => collect(DB::select(
                "SELECT table_name AS name
                FROM information_schema.tables
                WHERE table_type = 'BASE TABLE'
                ORDER BY table_name"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            default => throw new \RuntimeException("Unsupported database driver [{$driver}]."),
        };
    }

    /**
     * @param array<int, string> $tables
     * @return array<string, int>
     */
    private function rowCounts(array $tables): array
    {
        $counts = [];

        foreach ($tables as $table) {
            $counts[$table] = (int) DB::table($table)->count();
        }

        return $counts;
    }

    /**
     * @param array<int, string> $tables
     */
    private function emptyTables(string $driver, array $tables): void
    {
        if ($tables === []) {
            return;
        }

        if ($driver === 'pgsql') {
            $tableList = implode(', ', array_map(fn (string $table) => $this->quoteIdentifier($driver, $table), $tables));
            DB::statement("TRUNCATE TABLE {$tableList} RESTART IDENTITY CASCADE");

            return;
        }

        foreach ($tables as $table) {
            match ($driver) {
                'mysql' => DB::statement('TRUNCATE TABLE '.$this->quoteIdentifier($driver, $table)),
                'sqlite', 'sqlsrv' => DB::table($table)->delete(),
                default => throw new \RuntimeException("Unsupported database driver [{$driver}]."),
            };

            $this->resetSequence($driver, $table);
        }
    }

    private function disableForeignKeys(string $driver): void
    {
        match ($driver) {
            'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            'sqlsrv' => DB::statement('EXEC sp_MSforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"'),
            default => null,
        };
    }

    private function enableForeignKeys(string $driver): void
    {
        match ($driver) {
            'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            'sqlsrv' => DB::statement('EXEC sp_MSforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"'),
            default => null,
        };
    }

    private function resetSequence(string $driver, string $table): void
    {
        match ($driver) {
            'sqlite' => $this->resetSqliteSequence($table),
            'mysql' => $this->resetMysqlAutoIncrement($table),
            'pgsql' => $this->resetPostgresSequence($table),
            'sqlsrv' => $this->resetSqlServerIdentity($table),
            default => null,
        };
    }

    private function normalizeAdminAccount(int $adminId): void
    {
        $updates = [
            'id' => 1,
            'is_admin' => true,
            'status' => 'active',
            'deleted_at' => null,
            'profile_photo_path' => null,
            'target_position' => null,
            'remember_token' => null,
            'updated_at' => now(),
        ];

        $columns = array_flip(Schema::getColumnListing('users'));

        DB::table('users')
            ->where('id', $adminId)
            ->update(array_intersect_key($updates, $columns));
    }

    private function resetSqliteSequence(string $table): void
    {
        if (!Schema::hasColumn($table, 'id')) {
            return;
        }

        try {
            DB::table('sqlite_sequence')->where('name', $table)->delete();
        } catch (\Throwable) {
            // SQLite creates sqlite_sequence only when a table uses AUTOINCREMENT.
        }
    }

    private function resetMysqlAutoIncrement(string $table): void
    {
        if (!Schema::hasColumn($table, 'id')) {
            return;
        }

        $nextId = max(1, ((int) DB::table($table)->max('id')) + 1);

        DB::statement('ALTER TABLE '.$this->quoteIdentifier('mysql', $table).' AUTO_INCREMENT = '.$nextId);
    }

    private function resetPostgresSequence(string $table): void
    {
        if (!Schema::hasColumn($table, 'id')) {
            return;
        }

        $sequence = DB::selectOne(
            "SELECT pg_get_serial_sequence(?, 'id') AS sequence_name",
            [$table]
        );

        if (!($sequence?->sequence_name)) {
            return;
        }

        $nextId = max(1, (int) DB::table($table)->max('id'));

        DB::statement('SELECT setval(?, ?, true)', [$sequence->sequence_name, $nextId]);
    }

    private function resetSqlServerIdentity(string $table): void
    {
        if (!Schema::hasColumn($table, 'id')) {
            return;
        }

        $currentId = max(0, (int) DB::table($table)->max('id'));

        DB::statement('DBCC CHECKIDENT ('.$this->quoteIdentifier('sqlsrv', $table).', RESEED, '.$currentId.')');
    }

    private function quoteIdentifier(string $driver, string $identifier): string
    {
        $escaped = str_replace(
            $driver === 'mysql' ? '`' : '"',
            $driver === 'mysql' ? '``' : '""',
            $identifier
        );

        return $driver === 'mysql' ? "`{$escaped}`" : "\"{$escaped}\"";
    }

    private function deleteUploads(): void
    {
        $paths = collect([
            storage_path('app/public'),
            public_path('storage'),
        ])
            ->filter(fn (string $path) => File::exists($path))
            ->unique(fn (string $path) => realpath($path) ?: $path)
            ->values();

        foreach ($paths as $path) {
            foreach (File::directories($path) as $directory) {
                File::deleteDirectory($directory);
            }

            foreach (File::files($path) as $file) {
                if ($file->getFilename() !== '.gitignore') {
                    File::delete($file->getPathname());
                }
            }
        }

        $this->info('Uploaded files were deleted from public storage.');
    }
}
