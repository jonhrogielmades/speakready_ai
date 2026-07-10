<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeIdSequences extends Command
{
    protected $signature = 'app:normalize-id-sequences
        {--force : Run without an interactive confirmation}';

    protected $description = 'Reset ID sequences so empty tables start at 1 and non-empty tables continue after their highest ID.';

    public function handle(): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        if (!$this->option('force')) {
            $this->warn("This will normalize ID sequences in database [{$database}].");
            if (!$this->confirm('Continue?')) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $rows = [];

        foreach ($this->tables($driver) as $table) {
            if ($table === 'migrations' || !Schema::hasColumn($table, 'id')) {
                continue;
            }

            $rowCount = (int) DB::table($table)->count();
            $maxId = DB::table($table)->max('id');
            $nextId = $this->resetSequence($driver, $table, $maxId);

            $rows[] = [
                $table,
                $rowCount,
                $maxId ?: 0,
                $nextId,
            ];
        }

        $this->table(['Table', 'Rows', 'Max ID', 'Next ID'], $rows);
        $this->info('ID sequences normalized.');

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

    private function resetSequence(string $driver, string $table, mixed $maxId): int
    {
        $currentMaxId = (int) ($maxId ?: 0);
        $nextId = $currentMaxId + 1;

        match ($driver) {
            'mysql' => DB::statement('ALTER TABLE '.$this->quoteIdentifier($driver, $table).' AUTO_INCREMENT = '.$nextId),
            'pgsql' => $this->resetPostgresSequence($table, $currentMaxId),
            'sqlite' => $this->resetSqliteSequence($table),
            'sqlsrv' => $this->resetSqlServerIdentity($table, $currentMaxId),
            default => throw new \RuntimeException("Unsupported database driver [{$driver}]."),
        };

        return $nextId;
    }

    private function resetPostgresSequence(string $table, int $currentMaxId): void
    {
        $sequence = DB::selectOne(
            "SELECT pg_get_serial_sequence(?, 'id') AS sequence_name",
            [$table]
        );

        if (!($sequence?->sequence_name)) {
            return;
        }

        if ($currentMaxId > 0) {
            DB::statement('SELECT setval(?, ?, true)', [$sequence->sequence_name, $currentMaxId]);

            return;
        }

        DB::statement('SELECT setval(?, 1, false)', [$sequence->sequence_name]);
    }

    private function resetSqliteSequence(string $table): void
    {
        try {
            DB::table('sqlite_sequence')->where('name', $table)->delete();
        } catch (\Throwable) {
            // SQLite creates sqlite_sequence only when a table uses AUTOINCREMENT.
        }
    }

    private function resetSqlServerIdentity(string $table, int $currentMaxId): void
    {
        DB::statement("DBCC CHECKIDENT ('".str_replace("'", "''", $table)."', RESEED, {$currentMaxId})");
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
}
