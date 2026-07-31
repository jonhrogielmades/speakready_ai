<?php

namespace App\Console\Commands;

use App\Support\AiProviderSchema;
use Illuminate\Console\Command;

class EnsureAiProviderSchema extends Command
{
    protected $signature = 'app:ensure-ai-provider-schema
        {--force : Run the idempotent schema checks even if this process already checked them}
        {--create-missing : Create missing AI provider tables}';

    protected $description = 'Create or repair the AI provider schema required by guest and admin pages.';

    public function handle(): int
    {
        AiProviderSchema::ensure(
            (bool) $this->option('force'),
            (bool) $this->option('create-missing')
        );

        $this->info('AI provider schema is ready.');

        return self::SUCCESS;
    }
}
