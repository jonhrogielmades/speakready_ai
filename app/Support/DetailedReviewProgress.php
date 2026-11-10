<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/** Small, shared progress record for both queue workers and sync hosting. */
final class DetailedReviewProgress
{
    public static function mark(int $sessionId, string $status, int $validForSeconds = 180): void
    {
        Cache::put('feedback-review-status:'.$sessionId, [
            'status' => $status,
            'valid_until' => now()->addSeconds($validForSeconds)->timestamp,
        ], now()->addMinutes(15));
    }

    public static function status(int $sessionId): string
    {
        $progress = Cache::get('feedback-review-status:'.$sessionId, []);
        if (! is_array($progress) || (int) ($progress['valid_until'] ?? 0) <= now()->timestamp) {
            return 'failed';
        }

        $status = $progress['status'] ?? 'failed';

        // Only persisted review validation can establish readiness.
        return in_array($status, ['processing', 'waiting', 'unavailable', 'failed'], true)
            ? $status : 'failed';
    }
}
