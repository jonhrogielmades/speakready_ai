<?php

namespace App\Console\Commands;

use App\Support\GameSchema;
use Illuminate\Console\Command;

class EnsureGameSchema extends Command
{
    protected $signature = 'app:ensure-game-schema
        {--force : Run the idempotent schema checks even if this process already checked them}';

    protected $description = 'Create or repair the Learning Game session tables required by game routes.';

    public function handle(): int
    {
        GameSchema::ensure((bool) $this->option('force'));

        $this->info('Learning Game session schema is ready.');

        return self::SUCCESS;
    }
}
