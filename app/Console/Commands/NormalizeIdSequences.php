<?php

namespace App\Console\Commands;

use App\Support\DatabaseIdSequences;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeIdSequences extends Command
{
    protected $signature = 'app:normalize-id-sequences
        {--force : Run without an interactive confirmation}';

    protected $description = 'Reset ID sequences so empty tables start at 1 and non-empty tables continue after their highest ID.';

    public function handle(DatabaseIdSequences $sequences): int
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        if (!$this->option('force')) {
            $this->warn("This will normalize ID sequences in database [{$database}].");
            if (!$this->confirm('Continue?')) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $rows = [];

        foreach ($sequences->tables() as $table) {
            $result = $sequences->normalizeTable($table);

            if ($result === null) {
                continue;
            }

            $rows[] = [
                $result['table'],
                $result['row_count'],
                $result['max_id'],
                $result['next_id'],
            ];
        }

        $this->table(['Table', 'Rows', 'Max ID', 'Next ID'], $rows);
        $this->info('ID sequences normalized.');

        return self::SUCCESS;
    }
}
