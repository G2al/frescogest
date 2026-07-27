<?php

namespace Tests\Feature;

use App\Enums\EmployeeCompensationType;
use App\Filament\Pages\BusinessReports;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeWorkShift;
use App\Models\User;
use App\Services\Employees\EmployeeAccountService;
use App\Services\Employees\EmployeeCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_work_shift_calculates_worked_time_and_expected_variance(): void
    {
        $employee = $this->employee([
            'expected_daily_minutes' => 480,
        ]);

        $shift = EmployeeWorkShift::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-07-26',
            'started_at' => '08:00',
            'ended_at' => '17:00',
            'break_minutes' => 60,
        ]);

        $this->assertSame(480, $shift->worked_minutes);
        $this->assertSame(480, $shift->expected_minutes);
        $this->assertSame('8h 00m', $shift->worked_duration);
        $this->assertSame('0h 00m', $shift->variance_duration);
    }

    public function test_daily_compensation_is_counted_once_for_each_worked_day(): void
    {
        $employee = $this->employee([
            'compensation_type' => EmployeeCompensationType::Daily,
            'compensation_amount' => 80,
        ]);

        foreach ([
            ['2026-07-10', '08:00', '12:00'],
            ['2026-07-10', '13:00', '17:00'],
            ['2026-07-11', '08:00', '16:00'],
        ] as [$date, $start, $end]) {
            EmployeeWorkShift::create([
                'employee_id' => $employee->id,
                'work_date' => $date,
                'started_at' => $start,
                'ended_at' => $end,
                'break_minutes' => 0,
            ]);
        }

        $this->assertSame(160.0, app(EmployeeCostService::class)->forMonth(2026, 7));
    }

    public function test_monthly_compensation_is_prorated_when_employment_starts_during_the_month(): void
    {
        $this->employee([
            'compensation_type' => EmployeeCompensationType::Monthly,
            'compensation_amount' => 3100,
            'hired_on' => '2026-07-16',
        ]);

        $this->assertSame(1600.0, app(EmployeeCostService::class)->forMonth(2026, 7));
    }

    public function test_hourly_compensation_uses_the_effective_worked_minutes(): void
    {
        $employee = $this->employee([
            'compensation_type' => EmployeeCompensationType::Hourly,
            'compensation_amount' => 5,
        ]);

        $shift = EmployeeWorkShift::create([
            'employee_id' => $employee->id,
            'work_date' => '2026-07-26',
            'started_at' => '08:00',
            'ended_at' => '20:00',
            'break_minutes' => 60,
        ]);

        $this->assertSame('55.00', $shift->pay_amount);
        $this->assertSame(55.0, app(EmployeeCostService::class)->forMonth(2026, 7));
    }

    public function test_employee_can_login_and_record_the_daily_attendance(): void
    {
        $this->get(route('employee.login'))
            ->assertOk()
            ->assertSee('Accesso dipendenti');

        $employee = app(EmployeeAccountService::class)->create([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'mario.rossi@example.test',
            'phone' => '3331234567',
            'account_password' => 'password123',
            'compensation_type' => EmployeeCompensationType::Hourly->value,
            'compensation_amount' => 5,
            'expected_daily_minutes' => 480,
            'hired_on' => today()->toDateString(),
            'active' => true,
        ]);

        $this->post(route('employee.login.store'), [
            'email' => $employee->email,
            'password' => 'password123',
        ])->assertRedirect(route('employee.attendance'));

        $this->get(route('employee.attendance'))
            ->assertOk()
            ->assertSee('Buongiorno, Mario.');

        $this->actingAs($employee->user, 'employee')
            ->post(route('employee.attendance.store'), [
                'status' => 'present',
                'started_at' => '08:00',
                'ended_at' => '17:00',
                'break_minutes' => 60,
                'notes' => 'Giornata ordinaria',
            ])
            ->assertRedirect(route('employee.attendance'));

        $shift = $employee->workShifts()->firstOrFail();
        $this->assertSame(today()->toDateString(), $shift->work_date->toDateString());
        $this->assertSame('present', $shift->status);
        $this->assertSame(480, $shift->worked_minutes);
        $this->assertSame('40.00', $shift->pay_amount);
    }

    public function test_employee_can_record_an_absence_without_working_times(): void
    {
        $employee = app(EmployeeAccountService::class)->create([
            'first_name' => 'Anna',
            'last_name' => 'Verdi',
            'email' => 'anna.verdi@example.test',
            'phone' => '3337654321',
            'account_password' => 'password123',
            'compensation_type' => EmployeeCompensationType::Daily->value,
            'compensation_amount' => 80,
            'expected_daily_minutes' => 480,
            'hired_on' => today()->toDateString(),
            'active' => true,
        ]);

        $this->actingAs($employee->user, 'employee')
            ->post(route('employee.attendance.store'), [
                'status' => 'absent',
                'notes' => 'Malattia',
            ])
            ->assertRedirect(route('employee.attendance'));

        $this->assertDatabaseHas('employee_work_shifts', [
            'employee_id' => $employee->id,
            'status' => 'absent',
            'worked_minutes' => 0,
            'pay_amount' => 0,
        ]);
    }

    public function test_employee_can_filter_personal_attendance_history_by_quick_periods(): void
    {
        $employee = app(EmployeeAccountService::class)->create([
            'first_name' => 'Luca',
            'last_name' => 'Ottini',
            'email' => 'luca.ottini@example.test',
            'phone' => '3337654322',
            'account_password' => 'password123',
            'compensation_type' => EmployeeCompensationType::Hourly->value,
            'compensation_amount' => 5,
            'expected_daily_minutes' => 480,
            'hired_on' => today()->subMonth()->toDateString(),
            'active' => true,
        ]);

        foreach ([today(), today()->subDay(), today()->subDays(8)] as $date) {
            EmployeeWorkShift::create([
                'employee_id' => $employee->id,
                'work_date' => $date->toDateString(),
                'status' => 'absent',
                'notes' => $date->toDateString(),
            ]);
        }

        $this->actingAs($employee->user, 'employee')
            ->get(route('employee.attendance', ['period' => 'today']))
            ->assertOk()
            ->assertViewHas('period', 'today')
            ->assertViewHas('recentShifts', fn ($shifts): bool => $shifts->count() === 1
                && $shifts->first()->work_date->isToday());

        $this->actingAs($employee->user, 'employee')
            ->get(route('employee.attendance', ['period' => 'yesterday']))
            ->assertOk()
            ->assertViewHas('recentShifts', fn ($shifts): bool => $shifts->count() === 1
                && $shifts->first()->work_date->isYesterday());
    }

    public function test_employee_can_filter_personal_attendance_history_by_custom_dates(): void
    {
        $employee = app(EmployeeAccountService::class)->create([
            'first_name' => 'Luca',
            'last_name' => 'Ottini',
            'email' => 'luca.filtri@example.test',
            'phone' => '3337654323',
            'account_password' => 'password123',
            'compensation_type' => EmployeeCompensationType::Hourly->value,
            'compensation_amount' => 5,
            'expected_daily_minutes' => 480,
            'hired_on' => today()->subMonth()->toDateString(),
            'active' => true,
        ]);
        $selectedDate = today()->subDays(4);

        foreach ([$selectedDate, today()->subDays(10)] as $date) {
            EmployeeWorkShift::create([
                'employee_id' => $employee->id,
                'work_date' => $date->toDateString(),
                'status' => 'absent',
            ]);
        }

        $this->actingAs($employee->user, 'employee')
            ->get(route('employee.attendance', [
                'period' => 'custom',
                'from' => $selectedDate->toDateString(),
                'to' => $selectedDate->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('period', 'custom')
            ->assertViewHas('recentShifts', fn ($shifts): bool => $shifts->count() === 1
                && $shifts->first()->work_date->isSameDay($selectedDate));
    }

    public function test_employee_account_cannot_access_filament(): void
    {
        $employee = app(EmployeeAccountService::class)->create([
            'first_name' => 'Paolo',
            'last_name' => 'Neri',
            'email' => 'paolo.neri@example.test',
            'phone' => '3339999999',
            'account_password' => 'password123',
            'compensation_type' => EmployeeCompensationType::Daily->value,
            'compensation_amount' => 80,
            'expected_daily_minutes' => 480,
            'hired_on' => today()->toDateString(),
            'active' => true,
        ]);

        $this->assertFalse($employee->user->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_personnel_cost_is_included_in_the_business_report_result(): void
    {
        $this->employee([
            'compensation_type' => EmployeeCompensationType::Monthly,
            'compensation_amount' => 1200,
            'hired_on' => '2026-07-01',
        ]);

        $page = app(BusinessReports::class);
        $page->month = '2026-07';
        $summary = $page->summary();

        $this->assertSame(1200.0, $summary['personnelCosts']);
        $this->assertSame(1200.0, $summary['operatingCosts']);
        $this->assertSame(-1200.0, $summary['netResult']);
    }

    public function test_employee_filament_pages_open(): void
    {
        $admin = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
        ]);
        $employee = $this->employee();

        $this->actingAs($admin, 'admin');

        $this->get(EmployeeResource::getUrl('index'))->assertOk();
        $this->get(EmployeeResource::getUrl('create'))->assertOk();
        $this->get(EmployeeResource::getUrl('edit', ['record' => $employee]))->assertOk();
    }

    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'compensation_type' => EmployeeCompensationType::Daily,
            'compensation_amount' => 80,
            'expected_daily_minutes' => 480,
            'hired_on' => '2026-07-01',
            'active' => true,
        ], $attributes));
    }
}
