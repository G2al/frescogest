<?php

namespace Tests\Feature;

use App\Enums\EmployeeCompensationType;
use App\Filament\Pages\BusinessReports;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeWorkShift;
use App\Models\User;
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
