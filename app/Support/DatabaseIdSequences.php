<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class DatabaseIdSequences
{
    /**
     * @return array<int, string>
     */
    public function tables(?string $connectionName = null): array
    {
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();

        return match ($driver) {
            'mysql' => collect($connection->select(
                "SELECT table_name AS name
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_type = 'BASE TABLE'
                ORDER BY table_name"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            'pgsql' => collect($connection->select(
                "SELECT tablename AS name
                FROM pg_tables
                WHERE schemaname = current_schema()
                ORDER BY tablename"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            'sqlite' => collect($connection->select(
                "SELECT name
                FROM sqlite_master
                WHERE type = 'table'
                AND name NOT LIKE 'sqlite_%'
                ORDER BY name"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            'sqlsrv' => collect($connection->select(
                "SELECT table_name AS name
                FROM information_schema.tables
                WHERE table_type = 'BASE TABLE'
                ORDER BY table_name"
            ))->pluck('name')->map(fn ($name) => (string) $name)->all(),
            default => throw new \RuntimeException("Unsupported database driver [{$driver}]."),
        };
    }

    /**
     * @return array{table: string, row_count: int, max_id: int, next_id: int}|null
     */
    public function normalizeTable(string $table, ?string $connectionName = null): ?array
    {
        $connection = DB::connection($connectionName);

        if (!$this->canNormalizeTable($connection, $table)) {
            return null;
        }

        $rowCount = (int) $connection->table($table)->count();
        $maxId = (int) ($connection->table($table)->max('id') ?: 0);
        $nextId = $this->resetSequence($connection, $table, $maxId);

        return [
            'table' => $table,
            'row_count' => $rowCount,
            'max_id' => $maxId,
            'next_id' => $nextId,
        ];
    }

    /**
     * @return array{table: string, row_count: int, max_id: int, next_id: int}|null
     */
    public function normalizeTableIfEmpty(string $table, ?string $connectionName = null): ?array
    {
        $connection = DB::connection($connectionName);

        if (!$this->canNormalizeTable($connection, $table)) {
            return null;
        }

        $rowCount = (int) $connection->table($table)->count();

        if ($rowCount > 0) {
            return null;
        }

        $nextId = $this->resetSequence($connection, $table, 0);

        return [
            'table' => $table,
            'row_count' => 0,
            'max_id' => 0,
            'next_id' => $nextId,
        ];
    }

    public function tableNameFromDeleteSql(string $sql): ?string
    {
        if (!preg_match('/^\s*delete\s+from\s+((?:["`\[]?[A-Za-z0-9_]+["`\]]?\.)?["`\[]?[A-Za-z0-9_]+["`\]]?)/i', $sql, $matches)) {
            return null;
        }

        $parts = explode('.', $matches[1]);
        $table = trim((string) end($parts), '`"[] ');

        return preg_match('/^[A-Za-z0-9_]+$/', $table) === 1 ? $table : null;
    }

    private function canNormalizeTable(ConnectionInterface $connection, string $table): bool
    {
        if ($table === 'migrations' || str_starts_with($table, 'sqlite_')) {
            return false;
        }

        try {
            return $connection->getSchemaBuilder()->hasColumn($table, 'id');
        } catch (\Throwable) {
            return false;
        }
    }

    private function resetSequence(ConnectionInterface $connection, string $table, int $currentMaxId): int
    {
        $driver = $connection->getDriverName();
        $nextId = $currentMaxId + 1;

        match ($driver) {
            'mysql' => $connection->statement('ALTER TABLE '.$this->quoteIdentifier($driver, $table).' AUTO_INCREMENT = '.$nextId),
            'pgsql' => $this->resetPostgresSequence($connection, $table, $currentMaxId),
            'sqlite' => $this->resetSqliteSequence($connection, $table),
            'sqlsrv' => $this->resetSqlServerIdentity($connection, $table, $currentMaxId),
            default => throw new \RuntimeException("Unsupported database driver [{$driver}]."),
        };

        return $nextId;
    }

    private function resetPostgresSequence(ConnectionInterface $connection, string $table, int $currentMaxId): void
    {
        $sequence = $connection->selectOne(
            "SELECT pg_get_serial_sequence(?, 'id') AS sequence_name",
            [$table]
        );

        if (!($sequence?->sequence_name)) {
            return;
        }

        if ($currentMaxId > 0) {
            $connection->statement('SELECT setval(?, ?, true)', [$sequence->sequence_name, $currentMaxId]);

            return;
        }

        $connection->statement('SELECT setval(?, 1, false)', [$sequence->sequence_name]);
    }

    private function resetSqliteSequence(ConnectionInterface $connection, string $table): void
    {
        try {
            $connection->table('sqlite_sequence')->where('name', $table)->delete();
        } catch (\Throwable) {
            // SQLite creates sqlite_sequence only when a table uses AUTOINCREMENT.
        }
    }

    private function resetSqlServerIdentity(ConnectionInterface $connection, string $table, int $currentMaxId): void
    {
        $connection->statement("DBCC CHECKIDENT ('".str_replace("'", "''", $table)."', RESEED, {$currentMaxId})");
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
