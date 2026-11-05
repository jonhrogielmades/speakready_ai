<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateDashboardBubbleMessages implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly string $cacheKey,
        public readonly array $context,
        public readonly string $provider
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return $this->cacheKey;
    }

    public function handle(): void
    {
        try {
            if (is_array(Cache::get($this->cacheKey)) || ! Setting::enabled('aic_enable')) {
                return;
            }

            $messages = AIService::providerIsConfigured($this->provider)
                ? AIService::generateDashboardBubbleMessages($this->context, $this->provider)
                : AIService::fallbackDashboardBubbleMessages();

            Cache::put($this->cacheKey, $messages, now()->addHours(6));
        } finally {
            Cache::forget($this->cacheKey.':pending');
        }
    }
}
