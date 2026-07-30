<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeWorkShift;
use Illuminate\Support\Facades\DB;

class EmployeeAttendanceService
{
    public function recordToday(Employee $employee, array $data): EmployeeWorkShift
    {
        return DB::transaction(function () use ($employee, $data): EmployeeWorkShift {
            $shift = $employee->workShifts()
                ->whereDate('work_date', today()->toDateString())
                ->firstOrNew();

            $shift->fill([
                'work_date' => today()->toDateString(),
                'status' => $data['status'],
                'started_at' => $data['status'] === 'present' ? ($data['started_at'] ?? null) : null,
                'ended_at' => $data['status'] === 'present' ? ($data['ended_at'] ?? null) : null,
                'break_minutes' => $data['status'] === 'present' ? (int) ($data['break_minutes'] ?? 0) : 0,
                'expected_minutes' => $employee->expected_daily_minutes,
                'notes' => $data['notes'] ?? null,
            ]);
            $shift->employee()->associate($employee);
            $shift->save();

            return $shift;
        });
    }
}
