<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeAttendanceRequest;
use App\Services\Employees\EmployeeAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user('employee')->employee;
        $todayShift = $employee->workShifts()
            ->whereDate('work_date', today()->toDateString())
            ->latest('id')
            ->first();
        $recentShifts = $employee->workShifts()
            ->whereDate('work_date', '<=', today()->toDateString())
            ->latest('work_date')
            ->latest('id')
            ->limit(14)
            ->get();

        return view('employees.attendance.index', compact('employee', 'todayShift', 'recentShifts'));
    }

    public function store(
        StoreEmployeeAttendanceRequest $request,
        EmployeeAttendanceService $service,
    ): RedirectResponse {
        $service->recordToday($request->user('employee')->employee, $request->validated());

        return redirect()->route('employee.attendance')
            ->with('success', 'La presenza di oggi è stata registrata.');
    }
}
