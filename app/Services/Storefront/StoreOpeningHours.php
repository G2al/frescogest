<?php

namespace App\Services\Storefront;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class StoreOpeningHours
{
    public function isClosed(?DateTimeInterface $dateTime = null): bool
    {
        if (! config('storefront.daily_closure.enabled')) {
            return false;
        }

        $now = $this->now($dateTime);
        [$startsAt, $endsAt] = $this->periodContainingOrFollowing($now);

        return $now->greaterThanOrEqualTo($startsAt) && $now->lessThan($endsAt);
    }

    /**
     * @return array{is_closed: bool, server_time: string, closes_at: string, reopens_at: string}
     */
    public function status(?DateTimeInterface $dateTime = null): array
    {
        $now = $this->now($dateTime);
        [$startsAt, $endsAt] = $this->periodContainingOrFollowing($now);
        $isClosed = config('storefront.daily_closure.enabled')
            && $now->greaterThanOrEqualTo($startsAt)
            && $now->lessThan($endsAt);

        if (! $isClosed && $now->greaterThanOrEqualTo($endsAt)) {
            $startsAt = $startsAt->addDay();
            $endsAt = $endsAt->addDay();
        }

        return [
            'is_closed' => $isClosed,
            'server_time' => $now->toIso8601String(),
            'closes_at' => $startsAt->toIso8601String(),
            'reopens_at' => $endsAt->toIso8601String(),
        ];
    }

    private function now(?DateTimeInterface $dateTime): CarbonImmutable
    {
        $timezone = (string) config('storefront.daily_closure.timezone', 'Europe/Rome');

        return $dateTime
            ? CarbonImmutable::instance($dateTime)->setTimezone($timezone)
            : CarbonImmutable::now($timezone);
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function periodContainingOrFollowing(CarbonImmutable $now): array
    {
        $startsAt = $this->atConfiguredTime($now, (string) config('storefront.daily_closure.starts_at', '10:00'));
        $endsAt = $this->atConfiguredTime($now, (string) config('storefront.daily_closure.ends_at', '11:30'));

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $endsAt = $endsAt->addDay();

            if ($now->lessThan($startsAt) && $now->lessThan($endsAt->subDay())) {
                $startsAt = $startsAt->subDay();
                $endsAt = $endsAt->subDay();
            }
        }

        return [$startsAt, $endsAt];
    }

    private function atConfiguredTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        if (! preg_match('/^(?<hour>\d{1,2}):(?<minute>\d{2})$/', $time, $matches)) {
            throw new \InvalidArgumentException("Invalid store closure time [{$time}].");
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];

        if ($hour > 23 || $minute > 59) {
            throw new \InvalidArgumentException("Invalid store closure time [{$time}].");
        }

        return $date->startOfDay()->setTime($hour, $minute);
    }
}
