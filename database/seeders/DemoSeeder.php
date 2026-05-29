<?php

namespace Database\Seeders;

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceGenerator;
use App\Services\LeaveDayCalculator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the demo database seeds.
     *
     * Idempotent: can be re-run without duplicating data.
     */
    public function run(): void
    {
        // ------------------------------------------------------------------
        // Departments
        // ------------------------------------------------------------------

        $engineering = Department::firstOrCreate(
            ['code' => 'ENG'],
            ['name' => 'Engineering'],
        );

        $sales = Department::firstOrCreate(
            ['code' => 'SAL'],
            ['name' => 'Sales'],
        );

        // ------------------------------------------------------------------
        // Managers
        // ------------------------------------------------------------------

        $managerEng = User::firstOrCreate(
            ['email' => 'manager.eng@example.com'],
            [
                'name' => 'Alice Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
                'department_id' => $engineering->id,
                'employee_code' => 'EMP-2001',
                'joined_at' => Carbon::parse('2020-03-01'),
                'email_verified_at' => now(),
            ]
        );

        $managerSales = User::firstOrCreate(
            ['email' => 'manager.sales@example.com'],
            [
                'name' => 'Bob Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::Manager,
                'department_id' => $sales->id,
                'employee_code' => 'EMP-2002',
                'joined_at' => Carbon::parse('2019-07-15'),
                'email_verified_at' => now(),
            ]
        );

        // Set department heads (idempotent: writes the same value on every run)
        $engineering->update(['head_user_id' => $managerEng->id]);
        $sales->update(['head_user_id' => $managerSales->id]);

        // ------------------------------------------------------------------
        // Employees
        // ------------------------------------------------------------------

        $empEng1 = User::firstOrCreate(
            ['email' => 'emp.eng1@example.com'],
            [
                'name' => 'Charlie Engineer',
                'password' => Hash::make('password'),
                'role' => UserRole::Employee,
                'department_id' => $engineering->id,
                'manager_id' => $managerEng->id,
                'employee_code' => 'EMP-1001',
                'joined_at' => Carbon::parse('2022-01-10'),
                'email_verified_at' => now(),
            ]
        );

        $empEng2 = User::firstOrCreate(
            ['email' => 'emp.eng2@example.com'],
            [
                'name' => 'Diana Engineer',
                'password' => Hash::make('password'),
                'role' => UserRole::Employee,
                'department_id' => $engineering->id,
                'manager_id' => $managerEng->id,
                'employee_code' => 'EMP-1002',
                'joined_at' => Carbon::parse('2023-04-01'),
                'email_verified_at' => now(),
            ]
        );

        $empSales1 = User::firstOrCreate(
            ['email' => 'emp.sales1@example.com'],
            [
                'name' => 'Evan Sales',
                'password' => Hash::make('password'),
                'role' => UserRole::Employee,
                'department_id' => $sales->id,
                'manager_id' => $managerSales->id,
                'employee_code' => 'EMP-1003',
                'joined_at' => Carbon::parse('2021-09-20'),
                'email_verified_at' => now(),
            ]
        );

        $empSales2 = User::firstOrCreate(
            ['email' => 'emp.sales2@example.com'],
            [
                'name' => 'Fiona Sales',
                'password' => Hash::make('password'),
                'role' => UserRole::Employee,
                'department_id' => $sales->id,
                'manager_id' => $managerSales->id,
                'employee_code' => 'EMP-1004',
                'joined_at' => Carbon::parse('2024-02-05'),
                'email_verified_at' => now(),
            ]
        );

        // ------------------------------------------------------------------
        // Leave Balances (via service — idempotent firstOrCreate internally)
        // ------------------------------------------------------------------

        /** @var LeaveBalanceGenerator $generator */
        $generator = app(LeaveBalanceGenerator::class);
        $generator->generateForYear(now()->year);

        // ------------------------------------------------------------------
        // Sample Leave Requests
        // ------------------------------------------------------------------

        $annualLeave = LeaveType::where('code', 'ANNUAL')->first();

        if ($annualLeave) {
            /** @var LeaveDayCalculator $calculator */
            $calculator = app(LeaveDayCalculator::class);

            // Request 1: PENDING — emp.eng1 for a near-future weekday range (Mon–Wed)
            // 2026-06-08 = Monday, 2026-06-10 = Wednesday
            $pendingStart = Carbon::parse('2026-06-08');
            $pendingEnd = Carbon::parse('2026-06-10');
            $pendingDays = $calculator->calculate($pendingStart, $pendingEnd);

            $this->createLeaveRequestIfAbsent($empEng1->id, $pendingStart, [
                'leave_type_id' => $annualLeave->id,
                'end_date' => $pendingEnd->toDateString(),
                'start_period' => DayPeriod::Full,
                'end_period' => DayPeriod::Full,
                'total_days' => $pendingDays,
                'reason' => 'Family vacation',
                'status' => LeaveStatus::Pending,
                'approver_id' => null,
                'approved_at' => null,
            ]);

            // Request 2: APPROVED — emp.sales1 for a past weekday range (Mon–Tue)
            // 2026-05-04 = Monday, 2026-05-05 = Tuesday
            $approvedStart = Carbon::parse('2026-05-04');
            $approvedEnd = Carbon::parse('2026-05-05');
            $approvedDays = $calculator->calculate($approvedStart, $approvedEnd);

            $this->createLeaveRequestIfAbsent($empSales1->id, $approvedStart, [
                'leave_type_id' => $annualLeave->id,
                'end_date' => $approvedEnd->toDateString(),
                'start_period' => DayPeriod::Full,
                'end_period' => DayPeriod::Full,
                'total_days' => $approvedDays,
                'reason' => 'Personal errands',
                'status' => LeaveStatus::Approved,
                'approver_id' => $managerSales->id,
                'approved_at' => now(),
            ]);

            // Request 3: PENDING — emp.eng2 for a single weekday (2026-06-15 = Monday)
            $singleStart = Carbon::parse('2026-06-15');
            $singleEnd = Carbon::parse('2026-06-15');
            $singleDays = $calculator->calculate($singleStart, $singleEnd);

            $this->createLeaveRequestIfAbsent($empEng2->id, $singleStart, [
                'leave_type_id' => $annualLeave->id,
                'end_date' => $singleEnd->toDateString(),
                'start_period' => DayPeriod::Full,
                'end_period' => DayPeriod::Full,
                'total_days' => $singleDays,
                'reason' => 'Medical appointment',
                'status' => LeaveStatus::Pending,
                'approver_id' => null,
                'approved_at' => null,
            ]);

            // Request 4: APPROVED PM half-day — emp.eng2 (Diana) on 2026-05-19 (Tuesday)
            // start_period = Afternoon → works morning, off afternoon → PM label
            $halfDayPmDate = Carbon::parse('2026-05-19');

            $this->createLeaveRequestIfAbsent($empEng2->id, $halfDayPmDate, [
                'leave_type_id' => $annualLeave->id,
                'end_date' => $halfDayPmDate->toDateString(),
                'start_period' => DayPeriod::Afternoon,
                'end_period' => DayPeriod::Afternoon,
                'total_days' => 0.5,
                'reason' => 'Afternoon errand',
                'status' => LeaveStatus::Approved,
                'approver_id' => $managerEng->id,
                'approved_at' => now(),
            ]);

            // Request 5: APPROVED AM half-day — emp.sales2 (Fiona) on 2026-05-20 (Wednesday)
            // start_period = Morning → off morning, works afternoon → AM label
            $halfDayAmDate = Carbon::parse('2026-05-20');

            $this->createLeaveRequestIfAbsent($empSales2->id, $halfDayAmDate, [
                'leave_type_id' => $annualLeave->id,
                'end_date' => $halfDayAmDate->toDateString(),
                'start_period' => DayPeriod::Morning,
                'end_period' => DayPeriod::Morning,
                'total_days' => 0.5,
                'reason' => 'Morning appointment',
                'status' => LeaveStatus::Approved,
                'approver_id' => $managerSales->id,
                'approved_at' => now(),
            ]);
        }

        $this->command->info('Demo data seeded: 2 departments, 2 managers, 4 employees, balances generated, 5 sample leave requests (including 2 half-days).');
    }

    /**
     * Create a LeaveRequest only if no row exists for the same user and start date.
     *
     * Uses whereDate() so the comparison works correctly with SQLite date columns.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createLeaveRequestIfAbsent(int $userId, Carbon $startDate, array $attributes): void
    {
        $exists = LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereDate('start_date', $startDate->toDateString())
            ->exists();

        if (! $exists) {
            LeaveRequest::create(array_merge(
                ['user_id' => $userId, 'start_date' => $startDate->toDateString()],
                $attributes,
            ));
        }
    }
}
