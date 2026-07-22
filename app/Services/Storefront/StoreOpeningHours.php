<?php

namespace App\Services\Storefront;

use App\Enums\StoreClosureType;
use App\Models\StoreClosureSchedule;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Schema;

class StoreOpeningHours
{
    public function isClosed(?DateTimeInterface $dateTime = null): bool
    {
        return $this->status($dateTime)['is_closed'];
    }

    public function status(?DateTimeInterface $dateTime = null): array
    {
        $now = $this->now($dateTime);

        if (! config('storefront.daily_closure.enabled') || ! Schema::hasTable('store_closure_schedules')) {
            return $this->openStatus($now);
        }

        $periods = $this->closurePeriods($now);
        $current = collect($periods)->first(
            fn (array $period): bool => $now->greaterThanOrEqualTo($period['starts_at'])
                && $now->lessThan($period['ends_at'])
        );
        $next = $current ?? collect($periods)->first(
            fn (array $period): bool => $period['starts_at']->greaterThan($now)
        );

        return [
            'is_closed' => $current !== null,
            'server_time' => $now->toIso8601String(),
            'closes_at' => $next ? $next['starts_at']->toIso8601String() : null,
            'reopens_at' => $next ? $next['ends_at']->toIso8601String() : null,
            'message' => $current['message'] ?? null,
        ];
    }

    private function now(?DateTimeInterface $dateTime): CarbonImmutable
    {
        $timezone = (string) config('storefront.daily_closure.timezone', 'Europe/Rome');

        return $dateTime
            ? CarbonImmutable::instance($dateTime)->setTimezone($timezone)
            : CarbonImmutable::now($timezone);
    }

    private function closurePeriods(CarbonImmutable $now): array
    {
        $periods = [];

        StoreClosureSchedule::query()
            ->where('active', true)
            ->get()
            ->each(function (StoreClosureSchedule $schedule) use (&$periods, $now): void {
                if ($schedule->type === StoreClosureType::SpecificDate && $schedule->closure_date) {
                    $periods[] = $this->periodForDate($schedule, $schedule->closure_date->toImmutable());

                    return;
                }

                if ($schedule->type !== StoreClosureType::Recurring) {
                    return;
                }

                $weekdays = array_map('intval', $schedule->weekdays ?? []);
                $date = $now->subDay()->startOfDay();

                for ($offset = 0; $offset <= 8; $offset++) {
                    $candidate = $date->addDays($offset);

                    if (in_array($candidate->dayOfWeekIso, $weekdays, true)) {
                        $periods[] = $this->periodForDate($schedule, $candidate);
                    }
                }
            });

        usort(
            $periods,
            fn (array $left, array $right): int => $left['starts_at']->getTimestamp() <=> $right['starts_at']->getTimestamp(),
        );

        return $this->mergeOverlappingPeriods($periods);
    }

    private function periodForDate(StoreClosureSchedule $schedule, CarbonImmutable $date): array
    {
        $startsAt = $this->atTime($date, $schedule->starts_at);
        $endsAt = $this->atTime($date, $schedule->ends_at);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $endsAt = $endsAt->addDay();
        }

        return [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'message' => $schedule->message,
        ];
    }

    private function atTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $date->format('Y-m-d').' '.substr($time, 0, 8),
            $date->getTimezone(),
        );
    }

    private function mergeOverlappingPeriods(array $periods): array
    {
        $merged = [];

        foreach ($periods as $period) {
            $lastIndex = array_key_last($merged);

            if ($lastIndex === null || $period['starts_at']->greaterThan($merged[$lastIndex]['ends_at'])) {
                $merged[] = $period;

                continue;
            }

            if ($period['ends_at']->greaterThan($merged[$lastIndex]['ends_at'])) {
                $merged[$lastIndex]['ends_at'] = $period['ends_at'];
            }

            $merged[$lastIndex]['message'] ??= $period['message'];
        }

        return $merged;
    }

    private function openStatus(CarbonImmutable $now): array
    {
        return [
            'is_closed' => false,
            'server_time' => $now->toIso8601String(),
            'closes_at' => null,
            'reopens_at' => null,
            'message' => null,
        ];
    }
}
