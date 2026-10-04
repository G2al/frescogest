<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeAttendanceRequest;
use App\Models\EmployeeWorkShift;
use App\Services\Employees\EmployeeAttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user('employee')->employee;
        $today = CarbonImmutable::today();
        $todayShift = $employee->workShifts()
            ->whereDate('work_date', $today->toDateString())
            ->latest('id')
            ->first();

        $period = in_array($request->query('period'), [
            'recent',
            'today',
            'yesterday',
            'week',
            'month',
            'custom',
        ], true) ? $request->query('period') : 'recent';
        $from = $this->dateFromQuery($request, 'from');
        $to = $this->dateFromQuery($request, 'to');

        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $shiftsQuery = $employee->workShifts()
            ->whereDate('work_date', '<=', $today->toDateString());

        $this->applyPeriod($shiftsQuery, $period, $today, $from, $to);

        // Il totale va calcolato su TUTTE le registrazioni del periodo, non sulle
        // sole righe mostrate nell'elenco (limitato per non appesantire la pagina).
        $periodSummary = $period === 'recent'
            ? null
            : $this->summarize((clone $shiftsQuery)->get(), $period);

        $recentShifts = $shiftsQuery
            ->latest('work_date')
            ->latest('id')
            ->limit($period === 'recent' ? 14 : 90)
            ->get();

        return view('employees.attendance.index', compact(
            'employee',
            'todayShift',
            'recentShifts',
            'period',
            'from',
            'to',
            'periodSummary',
        ));
    }

    public function store(
        StoreEmployeeAttendanceRequest $request,
        EmployeeAttendanceService $service,
    ): RedirectResponse {
        $service->recordToday($request->user('employee')->employee, $request->validated());

        return redirect()->route('employee.attendance')
            ->with('success', 'La presenza di oggi è stata registrata.');
    }

    /**
     * @param  Collection<int, EmployeeWorkShift>  $shifts
     * @return array{worked_minutes: int, worked_duration: string, pay_amount: float, present_days: int, absent_days: int, open_shifts: int, label: string}
     */
    private function summarize(Collection $shifts, string $period): array
    {
        $workedMinutes = (int) $shifts->sum('worked_minutes');
        $openShifts = $shifts->filter(fn (EmployeeWorkShift $shift): bool => $shift->is_open)->count();

        return [
            'worked_minutes' => $workedMinutes,
            'worked_duration' => sprintf('%dh %02dm', intdiv($workedMinutes, 60), $workedMinutes % 60),
            'pay_amount' => (float) $shifts->sum('pay_amount'),
            'present_days' => $shifts->where('status', 'present')->count(),
            'absent_days' => $shifts->where('status', 'absent')->count(),
            'open_shifts' => $openShifts,
            'label' => match ($period) {
                'today' => 'oggi',
                'yesterday' => 'ieri',
                'week' => 'questa settimana',
                'month' => 'questo mese',
                'custom' => 'nel periodo selezionato',
                default => '',
            },
        ];
    }

    private function applyPeriod(
        HasMany $query,
        string $period,
        CarbonImmutable $today,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to,
    ): void {
        match ($period) {
            'today' => $query->whereDate('work_date', $today->toDateString()),
            'yesterday' => $query->whereDate('work_date', $today->subDay()->toDateString()),
            'week' => $query
                ->whereDate('work_date', '>=', $today->startOfWeek()->toDateString())
                ->whereDate('work_date', '<=', $today->toDateString()),
            'month' => $query
                ->whereDate('work_date', '>=', $today->startOfMonth()->toDateString())
                ->whereDate('work_date', '<=', $today->toDateString()),
            'custom' => $this->applyCustomPeriod($query, $from, $to),
            default => null,
        };
    }

    private function applyCustomPeriod(
        HasMany $query,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to,
    ): void {
        if ($from) {
            $query->whereDate('work_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('work_date', '<=', $to->toDateString());
        }
    }

    private function dateFromQuery(Request $request, string $key): ?CarbonImmutable
    {
        $value = $request->query($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
