<?php

namespace App\Console\Commands;

use App\Support\ScoreSchema;
use Illuminate\Console\Command;

class EnsureScoreSchema extends Command
{
    protected $signature = 'app:ensure-score-schema
        {--force : Run the idempotent schema checks even if this process already checked them}
        {--create-missing : Create the scores table if it is missing and dependencies exist}';

    protected $description = 'Create or repair the interview score schema required by feedback reports.';

    public function handle(): int
    {
        ScoreSchema::ensure(
            (bool) $this->option('force'),
            (bool) $this->option('create-missing')
        );

        $this->info('Interview score schema is ready.');

        return self::SUCCESS;
    }
}
