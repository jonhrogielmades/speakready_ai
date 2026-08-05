<?php

namespace App\Console\Commands;

use App\Support\InterviewAnswerSchema;
use Illuminate\Console\Command;

class EnsureInterviewAnswerSchema extends Command
{
    protected $signature = 'app:ensure-interview-answer-schema
        {--force : Run the idempotent schema checks even if this process already checked them}
        {--create-missing : Create the interview_answers table if it is missing and dependencies exist}';

    protected $description = 'Create or repair the interview answer schema required by interview sessions.';

    public function handle(): int
    {
        InterviewAnswerSchema::ensure(
            (bool) $this->option('force'),
            (bool) $this->option('create-missing')
        );

        $this->info('Interview answer schema is ready.');

        return self::SUCCESS;
    }
}
