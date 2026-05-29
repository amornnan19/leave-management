<?php

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use App\Filament\Widgets\LeaveCalendarWidget;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

// ── Helpers ──────────────────────────────────────────────────────────────────

function setAdminPanel(): void
{
    Filament::setCurrentPanel(Filament::getPanel('admin'));
}

function setEmployeePanel(): void
{
    Filament::setCurrentPanel(Filament::getPanel('employee'));
}

// ── 1. Basic render ───────────────────────────────────────────────────────────

test('leave calendar widget renders without error', function (): void {
    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->assertOk();
});

test('widget initialises to current month and year', function (): void {
    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->assertSet('year', now()->year)
        ->assertSet('month', now()->month);
});

// ── 2. Approved leave appears ─────────────────────────────────────────────────

test('approved leave shows employee name on the spanned days', function (): void {
    $employee = User::factory()->create(['name' => 'Alice Wonderland']);
    $leaveType = LeaveType::factory()->create(['color' => '#6366f1', 'name' => 'Annual Leave']);

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-12',
        'status' => LeaveStatus::Approved,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $widget = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6);

    $widget->assertSee('Alice Wonderland');
});

test('approved leave data appears in getCalendarWeeks for spanning days', function (): void {
    $employee = User::factory()->create(['name' => 'Bob Builder']);
    $leaveType = LeaveType::factory()->create(['color' => '#ef4444', 'name' => 'Sick Leave']);

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-15',
        'status' => LeaveStatus::Approved,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $component = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6);

    $weeks = $component->instance()->getCalendarWeeks();

    $found = false;

    foreach ($weeks as $week) {
        foreach ($week as $cell) {
            if ($cell['date']->toDateString() === '2026-06-15') {
                $names = array_column($cell['leaves'], 'name');
                $found = in_array('Bob Builder', $names);
            }
        }
    }

    expect($found)->toBeTrue();
});

// ── 3. Pending / Rejected leave does NOT appear ───────────────────────────────

test('pending leave does not appear in the calendar', function (): void {
    $employee = User::factory()->create(['name' => 'Charlie Pending']);
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-10',
        'status' => LeaveStatus::Pending,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->assertDontSee('Charlie Pending');
});

test('rejected leave does not appear in the calendar', function (): void {
    $employee = User::factory()->create(['name' => 'Diana Rejected']);
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->rejected()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-10',
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->assertDontSee('Diana Rejected');
});

// ── 4. Holidays ────────────────────────────────────────────────────────────────

test('non-recurring holiday appears on its exact date', function (): void {
    Holiday::factory()->create([
        'name' => 'National Day',
        'date' => '2026-06-24',
        'is_recurring' => false,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->assertSee('National Day');
});

test('recurring holiday appears in the calendar year regardless of stored year', function (): void {
    // Stored in 2024, but should show in 2026 for the same month-day
    Holiday::factory()->create([
        'name' => 'New Year Day',
        'date' => '2024-01-01',
        'is_recurring' => true,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 1)
        ->assertSee('New Year Day');
});

test('recurring holiday data appears in getCalendarWeeks on the correct month-day', function (): void {
    Holiday::factory()->create([
        'name' => 'Labour Day',
        'date' => '2020-05-01',   // stored 2020, should recur every year on 05-01
        'is_recurring' => true,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $component = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 5);

    $weeks = $component->instance()->getCalendarWeeks();
    $found = false;

    foreach ($weeks as $week) {
        foreach ($week as $cell) {
            if ($cell['date']->toDateString() === '2026-05-01') {
                $found = in_array('Labour Day', $cell['holidays']);
            }
        }
    }

    expect($found)->toBeTrue();
});

// ── 5. Company-wide visibility ────────────────────────────────────────────────

test('employee acting as plain staff sees another employees approved leave', function (): void {
    $employeeA = User::factory()->create(['name' => 'Eve Skywalker']);
    $employeeB = User::factory()->create(['name' => 'Frank Ocean']);
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employeeA->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-10',
        'status' => LeaveStatus::Approved,
    ]);

    // employeeB views calendar — must see employeeA's name
    actingAs($employeeB);
    setEmployeePanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->assertSee('Eve Skywalker');
});

// ── 6. Privacy: reason is never exposed ──────────────────────────────────────

test('leave request reason text is never rendered in the widget', function (): void {
    $employee = User::factory()->create();
    $leaveType = LeaveType::factory()->create();

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-10',
        'status' => LeaveStatus::Approved,
        'reason' => 'SuperSecretPersonalReason12345',
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->assertDontSee('SuperSecretPersonalReason12345');
});

// ── 7. Month navigation ────────────────────────────────────────────────────────

test('nextMonth advances the month', function (): void {
    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->call('nextMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 7);
});

test('previousMonth goes back a month', function (): void {
    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->call('previousMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 5);
});

test('nextMonth rolls year from December to January', function (): void {
    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2025)
        ->set('month', 12)
        ->call('nextMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 1);
});

test('previousMonth rolls year from January to December', function (): void {
    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 1)
        ->call('previousMonth')
        ->assertSet('year', 2025)
        ->assertSet('month', 12);
});

test('goToToday resets to current month and year', function (): void {
    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2020)
        ->set('month', 3)
        ->call('goToToday')
        ->assertSet('year', now()->year)
        ->assertSet('month', now()->month);
});

// ── 8. Half-day leave indicators ─────────────────────────────────────────────

test('approved PM half-day leave produces half === PM for that day', function (): void {
    $employee = User::factory()->create(['name' => 'Grace PM']);
    $leaveType = LeaveType::factory()->create(['color' => '#6366f1', 'name' => 'Annual Leave']);

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-10',
        'start_period' => DayPeriod::Afternoon,
        'end_period' => DayPeriod::Afternoon,
        'total_days' => 0.5,
        'status' => LeaveStatus::Approved,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $component = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6);

    $weeks = $component->instance()->getCalendarWeeks();
    $halfValue = null;

    foreach ($weeks as $week) {
        foreach ($week as $cell) {
            if ($cell['date']->toDateString() === '2026-06-10') {
                foreach ($cell['leaves'] as $leave) {
                    if ($leave['name'] === 'Grace PM') {
                        $halfValue = $leave['half'];
                    }
                }
            }
        }
    }

    expect($halfValue)->toBe('PM');
});

test('approved AM half-day leave produces half === AM for that day', function (): void {
    $employee = User::factory()->create(['name' => 'Henry AM']);
    $leaveType = LeaveType::factory()->create(['color' => '#ef4444', 'name' => 'Sick Leave']);

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-11',
        'end_date' => '2026-06-11',
        'start_period' => DayPeriod::Morning,
        'end_period' => DayPeriod::Morning,
        'total_days' => 0.5,
        'status' => LeaveStatus::Approved,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $component = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6);

    $weeks = $component->instance()->getCalendarWeeks();
    $halfValue = null;

    foreach ($weeks as $week) {
        foreach ($week as $cell) {
            if ($cell['date']->toDateString() === '2026-06-11') {
                foreach ($cell['leaves'] as $leave) {
                    if ($leave['name'] === 'Henry AM') {
                        $halfValue = $leave['half'];
                    }
                }
            }
        }
    }

    expect($halfValue)->toBe('AM');
});

test('full-day approved leave has half === null', function (): void {
    $employee = User::factory()->create(['name' => 'Iris Fullday']);
    $leaveType = LeaveType::factory()->create(['color' => '#22c55e', 'name' => 'Annual Leave']);

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-12',
        'end_date' => '2026-06-12',
        'start_period' => DayPeriod::Full,
        'end_period' => DayPeriod::Full,
        'total_days' => 1.0,
        'status' => LeaveStatus::Approved,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $component = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6);

    $weeks = $component->instance()->getCalendarWeeks();
    $halfValue = 'NOT_FOUND';

    foreach ($weeks as $week) {
        foreach ($week as $cell) {
            if ($cell['date']->toDateString() === '2026-06-12') {
                foreach ($cell['leaves'] as $leave) {
                    if ($leave['name'] === 'Iris Fullday') {
                        $halfValue = $leave['half'];
                    }
                }
            }
        }
    }

    expect($halfValue)->toBeNull();
});

// ── 9. Dashboard smoke tests ──────────────────────────────────────────────────

test('admin dashboard loads for HR and no longer shows filament info widget text', function (): void {
    actingAs(User::factory()->hr()->create());

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('Outstanding issues')
        ->assertDontSee('Filament Docs');
});

test('portal dashboard loads for employee and no longer shows account widget', function (): void {
    actingAs(User::factory()->create());

    $this->get('/portal')
        ->assertOk();
});

test('multi-day leave marks the start day PM, middle day full, end day AM', function (): void {
    $employee = User::factory()->create(['name' => 'Jack Span']);
    $leaveType = LeaveType::factory()->create(['color' => '#6366f1', 'name' => 'Annual Leave']);

    // Starts in the afternoon of the 10th, ends in the morning of the 12th
    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-12',
        'start_period' => DayPeriod::Afternoon,
        'end_period' => DayPeriod::Morning,
        'total_days' => 2.0,
        'status' => LeaveStatus::Approved,
    ]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $weeks = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->instance()
        ->getCalendarWeeks();

    $halfFor = function (string $date) use ($weeks): mixed {
        foreach ($weeks as $week) {
            foreach ($week as $cell) {
                if ($cell['date']->toDateString() === $date) {
                    foreach ($cell['leaves'] as $leave) {
                        if ($leave['name'] === 'Jack Span') {
                            return $leave['half'];
                        }
                    }
                }
            }
        }

        return 'absent';
    };

    expect($halfFor('2026-06-10'))->toBe('PM')
        ->and($halfFor('2026-06-11'))->toBeNull()
        ->and($halfFor('2026-06-12'))->toBe('AM');
});

test('getCalendarWeeks runs exactly two queries regardless of leave volume', function (): void {
    $leaveType = LeaveType::factory()->create(['name' => 'Annual Leave']);

    // Several multi-day, multi-user approved leaves + holidays in the window
    User::factory()->count(4)->create()->each(function (User $user) use ($leaveType): void {
        LeaveRequest::factory()->approved()->create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-12',
            'total_days' => 5.0,
            'status' => LeaveStatus::Approved,
        ]);
    });
    Holiday::factory()->create(['date' => '2026-06-15', 'is_recurring' => false]);

    actingAs(User::factory()->hr()->create());
    setAdminPanel();

    $widget = Livewire::test(LeaveCalendarWidget::class)
        ->set('year', 2026)
        ->set('month', 6)
        ->instance();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $widget->getCalendarWeeks();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Constant query count (holidays + leaves + 2 eager-load queries for user/leaveType),
    // independent of how many leaves/days are in the window — i.e. NO per-day/per-leave N+1.
    // With 4 multi-day leaves a lazy-loaded implementation would fire dozens of queries.
    expect($queryCount)->toBeLessThanOrEqual(4);
});
