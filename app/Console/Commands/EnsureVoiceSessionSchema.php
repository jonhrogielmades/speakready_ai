<?php

namespace App\Console\Commands;

use App\Support\VoiceSessionSchema;
use Illuminate\Console\Command;

class EnsureVoiceSessionSchema extends Command
{
    protected $signature = 'app:ensure-voice-schema
        {--force : Run the idempotent schema checks even if this process already checked them}
        {--create-missing : Create the voice_sessions table if it is missing and users exists}';

    protected $description = 'Create or repair the Voice Rehearsal session schema required by voice routes.';

    public function handle(): int
    {
        VoiceSessionSchema::ensure(
            (bool) $this->option('force'),
            (bool) $this->option('create-missing')
        );

        $this->info('Voice Rehearsal session schema is ready.');

        return self::SUCCESS;
    }
}
