<?php

namespace App\Services\Reports;

class TrendService
{
    public function compare(float $current, float $previous, bool $lowerIsBetter = false): array
    {
        $difference = $current - $previous;
        $direction = abs($difference) < 0.005 ? 'flat' : ($difference > 0 ? 'up' : 'down');
        $percentage = abs($previous) > 0.005
            ? abs($difference / $previous * 100)
            : (abs($current) > 0.005 ? 100 : 0);
        $favorable = $direction === 'flat' || ($lowerIsBetter ? $direction === 'down' : $direction === 'up');

        return compact('direction', 'percentage', 'favorable');
    }
}
