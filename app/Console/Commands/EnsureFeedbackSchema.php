<?php

namespace App\Console\Commands;

use App\Support\FeedbackSchema;
use Illuminate\Console\Command;

class EnsureFeedbackSchema extends Command
{
    protected $signature = 'app:ensure-feedback-schema
        {--force : Run the idempotent schema checks even if this process already checked them}
        {--create-missing : Create the feedback table if it is missing and dependencies exist}';

    protected $description = 'Create or repair the feedback schema required by interview reports.';

    public function handle(): int
    {
        FeedbackSchema::ensure(
            (bool) $this->option('force'),
            (bool) $this->option('create-missing')
        );

        $this->info('Interview feedback schema is ready.');

        return self::SUCCESS;
    }
}
