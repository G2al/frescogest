<?php

namespace App\Services\Employees;

use App\Enums\EmployeeCompensationType;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class EmployeeCostService
{
    public function forMonth(int $year, int $month): float
    {
        $timezone = config('app.timezone');
        $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0, $timezone);
        $end = $start->endOfMonth()->startOfDay();

        return round($this->employeesDuring($start, $end)
            ->get()
            ->sum(fn (Employee $employee): float => $this->forEmployeeMonth($employee, $year, $month)), 2);
    }

    public function forEmployeeMonth(Employee $employee, int $year, int $month): float
    {
        $timezone = config('app.timezone');
        $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0, $timezone);
        $end = $start->endOfMonth()->startOfDay();

        return round($this->employeeCost($employee, $start, $end), 2);
    }

    public function workedMinutesForMonth(Employee $employee, int $year, int $month): int
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();

        return (int) $employee->workShifts()
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->sum('worked_minutes');
    }

    public function total(): float
    {
        $firstHire = Employee::query()->min('hired_on');

        if (! $firstHire) {
            return 0;
        }

        $start = CarbonImmutable::parse($firstHire)->startOfMonth();
        $end = now()->toImmutable()->endOfMonth();
        $total = 0.0;

        while ($start->lessThanOrEqualTo($end)) {
            $total += $this->forMonth($start->year, $start->month);
            $start = $start->addMonth();
        }

        return round($total, 2);
    }

    private function employeesDuring(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Employee::query()
            ->whereDate('hired_on', '<=', $end)
            ->where(function (Builder $query) use ($start): void {
                $query->whereNull('terminated_on')->orWhereDate('terminated_on', '>=', $start);
            });
    }

    private function employeeCost(Employee $employee, CarbonImmutable $start, CarbonImmutable $end): float
    {
        if ($employee->compensation_type === EmployeeCompensationType::Daily) {
            $workedDays = $employee->workShifts()
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                ->distinct()
                ->count('work_date');

            return $workedDays * (float) $employee->compensation_amount;
        }

        $timezone = config('app.timezone');
        $employmentStart = CarbonImmutable::createFromFormat('!Y-m-d', $employee->hired_on->format('Y-m-d'), $timezone)->max($start);
        $employmentEnd = $employee->terminated_on
            ? CarbonImmutable::createFromFormat('!Y-m-d', $employee->terminated_on->format('Y-m-d'), $timezone)->min($end)
            : $end;

        if ($employmentStart->greaterThan($employmentEnd)) {
            return 0;
        }

        $coveredDays = (int) $employmentStart->diffInDays($employmentEnd) + 1;

        return (float) $employee->compensation_amount * ($coveredDays / $start->daysInMonth);
    }
}
