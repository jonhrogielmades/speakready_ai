<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PracticeStreakService
{
    /**
     * @param  iterable<int, CarbonInterface|string>  $practiceDates
     * @return array{current: int, longest: int, last_activity_date: ?string}
     */
    public function calculate(iterable $practiceDates, ?CarbonInterface $today = null): array
    {
        $dates = collect($practiceDates)
            ->map(fn ($date): string => Carbon::parse($date)->toDateString())
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return [
                'current' => 0,
                'longest' => 0,
                'last_activity_date' => null,
            ];
        }

        return [
            'current' => $this->current($dates, $today ?? Carbon::today()),
            'longest' => $this->longest($dates),
            'last_activity_date' => $dates->last(),
        ];
    }

    /** @param Collection<int, string> $dates */
    private function current(Collection $dates, CarbonInterface $today): int
    {
        $dateSet = $dates->flip();
        $cursor = Carbon::parse($today)->startOfDay();

        if (! $dateSet->has($cursor->toDateString())) {
            $cursor->subDay();
        }

        $current = 0;
        while ($dateSet->has($cursor->toDateString())) {
            $current++;
            $cursor->subDay();
        }

        return $current;
    }

    /** @param Collection<int, string> $dates */
    private function longest(Collection $dates): int
    {
        $longest = 0;
        $run = 0;
        $previous = null;

        foreach ($dates as $dateString) {
            $date = Carbon::parse($dateString);
            $run = $previous && (int) $previous->diffInDays($date) === 1 ? $run + 1 : 1;
            $longest = max($longest, $run);
            $previous = $date;
        }

        return $longest;
    }
}
