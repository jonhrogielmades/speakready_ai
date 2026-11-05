<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RecoveryEmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendRecoveryEmailVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [15, 60];

    public function __construct(public readonly int $userId)
    {
        $this->afterCommit();
    }

    public function handle(RecoveryEmailVerification $verification): void
    {
        $user = User::find($this->userId);

        if ($user) {
            $verification->send($user);
        }
    }

    public function failed(Throwable $error): void
    {
        Log::warning('Recovery email verification could not be delivered.', [
            'user_id' => $this->userId,
            'exception_type' => $error::class,
        ]);
    }
}
