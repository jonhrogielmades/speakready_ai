<?php

namespace App\Console\Commands;

use App\Support\VoiceSessionSchema;
use Illuminate\Console\Command;
use Throwable;

class EnsureVoiceSessionSchema extends Command
{
    protected $signature = 'app:ensure-voice-schema
        {--force : Run the idempotent schema checks even if this process already checked them}
        {--create-missing : Create the voice_sessions table if it is missing and users exists}';

    protected $description = 'Create or repair the voice session schema required by speaking practice.';

    public function handle(): int
    {
        try {
            VoiceSessionSchema::ensure(
                (bool) $this->option('force'),
                (bool) $this->option('create-missing')
            );

            if (! VoiceSessionSchema::hasRequiredColumns()) {
                $this->error('Voice session schema is not ready. Run migrations first so the users table exists.');

                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            $this->error('Voice session schema repair failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Voice session schema is ready.');

        return self::SUCCESS;
    }
}
