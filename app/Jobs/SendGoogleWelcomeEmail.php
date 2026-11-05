<?php

namespace App\Jobs;

use App\Mail\GoogleWelcomeMail;
use App\Models\User;
use App\Services\BrevoTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendGoogleWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [15, 60];

    public function __construct(public readonly int $userId)
    {
        $this->afterCommit();
    }

    public function handle(BrevoTransactionalMail $brevoMailer): void
    {
        $user = User::find($this->userId);

        if (! $user || ! $user->isGoogleAccount() || blank($user->email)) {
            return;
        }

        $mail = new GoogleWelcomeMail($user);

        if ($brevoMailer->isConfigured()) {
            $brevoMailer->sendGoogleWelcome($user, $mail);

            return;
        }

        Mail::to($user->email)->send($mail);
    }

    public function failed(Throwable $error): void
    {
        Log::warning('Google welcome email could not be delivered.', [
            'user_id' => $this->userId,
            'exception_type' => $error::class,
        ]);
    }
}
