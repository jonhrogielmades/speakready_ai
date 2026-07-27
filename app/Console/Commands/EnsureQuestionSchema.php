<?php

namespace App\Console\Commands;

use App\Support\QuestionSchema;
use Illuminate\Console\Command;

class EnsureQuestionSchema extends Command
{
    protected $signature = 'app:ensure-question-schema
        {--force : Run the idempotent schema checks even if this process already checked them}
        {--create-missing : Create the questions table if it is missing and categories exists}';

    protected $description = 'Create or repair the interview question schema required by interview starts.';

    public function handle(): int
    {
        QuestionSchema::ensure(
            (bool) $this->option('force'),
            (bool) $this->option('create-missing')
        );

        $this->info('Interview question schema is ready.');

        return self::SUCCESS;
    }
}
