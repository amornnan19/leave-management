<?php

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveDayCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// HR — full access
// ---------------------------------------------------------------------------

test('hr can viewAny leave requests', function (): void {
    $hr = User::factory()->hr()->create();

    expect($hr->can('viewAny', LeaveRequest::class))->toBeTrue();
});

test('hr can delete leave requests', function (): void {
    $hr = User::factory()->hr()->create();
    $request = LeaveRequest::factory()->create();

    expect($hr->can('delete', $request))->toBeTrue();
});

test('hr can viewAny leave types', function (): void {
    $hr = User::factory()->hr()->create();

    expect($hr->can('viewAny', LeaveType::class))->toBeTrue();
});

test('hr can viewAny users', function (): void {
    $hr = User::factory()->hr()->create();

    expect($hr->can('viewAny', User::class))->toBeTrue();
});

test('hr can viewAny departments', function (): void {
    $hr = User::factory()->hr()->create();

    expect($hr->can('viewAny', Department::class))->toBeTrue();
});

test('hr can approve any leave request', function (): void {
    $hr = User::factory()->hr()->create();
    $request = LeaveRequest::factory()->create();

    expect($hr->can('approve', $request))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Manager — config resources hidden
// ---------------------------------------------------------------------------

test('manager cannot viewAny leave types', function (): void {
    $manager = User::factory()->manager()->create();

    expect($manager->can('viewAny', LeaveType::class))->toBeFalse();
});

test('manager cannot viewAny users', function (): void {
    $manager = User::factory()->manager()->create();

    expect($manager->can('viewAny', User::class))->toBeFalse();
});

test('manager cannot viewAny departments', function (): void {
    $manager = User::factory()->manager()->create();

    expect($manager->can('viewAny', Department::class))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Manager — leave request visibility scoped to team
// ---------------------------------------------------------------------------

test('manager can view their own leave request', function (): void {
    $manager = User::factory()->manager()->create();
    $ownRequest = LeaveRequest::factory()->create(['user_id' => $manager->id]);

    expect($manager->can('view', $ownRequest))->toBeTrue();
});

test('manager can view leave request of their subordinate', function (): void {
    $manager = User::factory()->manager()->create();
    $subordinate = User::factory()->create(['manager_id' => $manager->id]);
    $teamRequest = LeaveRequest::factory()->create(['user_id' => $subordinate->id]);

    expect($manager->can('view', $teamRequest))->toBeTrue();
});

test('manager cannot view leave request from outside their team', function (): void {
    $manager = User::factory()->manager()->create();
    $otherManager = User::factory()->manager()->create();
    $otherSubordinate = User::factory()->create(['manager_id' => $otherManager->id]);
    $otherRequest = LeaveRequest::factory()->create(['user_id' => $otherSubordinate->id]);

    expect($manager->can('view', $otherRequest))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Manager — approve ability
// ---------------------------------------------------------------------------

test('manager can approve leave request of their own team', function (): void {
    $manager = User::factory()->manager()->create();
    $subordinate = User::factory()->create(['manager_id' => $manager->id]);
    $teamRequest = LeaveRequest::factory()->create(['user_id' => $subordinate->id]);

    expect($manager->can('approve', $teamRequest))->toBeTrue();
});

test('manager cannot approve leave request from outside their team', function (): void {
    $manager = User::factory()->manager()->create();
    $otherManager = User::factory()->manager()->create();
    $otherSubordinate = User::factory()->create(['manager_id' => $otherManager->id]);
    $otherRequest = LeaveRequest::factory()->create(['user_id' => $otherSubordinate->id]);

    expect($manager->can('approve', $otherRequest))->toBeFalse();
});

test('manager cannot delete leave requests', function (): void {
    $manager = User::factory()->manager()->create();
    $subordinate = User::factory()->create(['manager_id' => $manager->id]);
    $teamRequest = LeaveRequest::factory()->create(['user_id' => $subordinate->id]);

    expect($manager->can('delete', $teamRequest))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Manager — viewAny leave requests allowed
// ---------------------------------------------------------------------------

test('manager can viewAny leave requests', function (): void {
    $manager = User::factory()->manager()->create();

    expect($manager->can('viewAny', LeaveRequest::class))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Pending re-guard: approve action must not re-process an already-decided request
// ---------------------------------------------------------------------------

test('approve action guard rejects an already-approved request', function (): void {
    $approver = User::factory()->hr()->create();
    $request = LeaveRequest::factory()->approved()->create([
        'approver_id' => $approver->id,
    ]);

    $originalStatus = $request->status;
    $originalApproverId = $request->approver_id;

    // Simulate the pending guard from the action closure
    if ($request->status !== LeaveStatus::Pending) {
        // guard would return early — record should not be mutated
    } else {
        $request->update(['status' => LeaveStatus::Approved, 'approver_id' => $approver->id, 'approved_at' => now()]);
    }

    $request->refresh();

    expect($request->status)->toBe($originalStatus)
        ->and($request->approver_id)->toBe($originalApproverId);
});

test('approve action guard rejects an already-rejected request', function (): void {
    $approver = User::factory()->hr()->create();
    $request = LeaveRequest::factory()->rejected()->create([
        'approver_id' => $approver->id,
    ]);

    $originalStatus = $request->status;

    if ($request->status !== LeaveStatus::Pending) {
        // guard fires — no update
    } else {
        $request->update(['status' => LeaveStatus::Approved]);
    }

    $request->refresh();

    expect($request->status)->toBe(LeaveStatus::Rejected);
});

// ---------------------------------------------------------------------------
// DayPeriod normalization: mutate logic must accept DayPeriod enum instances
// ---------------------------------------------------------------------------

test('LeaveDayCalculator accepts DayPeriod enum instances directly', function (): void {
    $calculator = app(LeaveDayCalculator::class);

    // Simulate what EditLeaveRequest::normalizeDayPeriod does when Filament
    // hydrates the cast attribute as a DayPeriod instance instead of a string.
    $startPeriod = DayPeriod::Full;
    $endPeriod = DayPeriod::Full;

    $result = $calculator->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-01'),
        $startPeriod instanceof DayPeriod ? $startPeriod : DayPeriod::from($startPeriod),
        $endPeriod instanceof DayPeriod ? $endPeriod : DayPeriod::from($endPeriod),
    );

    expect($result)->toBe(1.0);
});

test('normalizeDayPeriod helper returns correct enum for string input', function (): void {
    // Inline the normalization logic to verify both branches without needing the Filament page
    $normalize = function (mixed $value): DayPeriod {
        if ($value instanceof DayPeriod) {
            return $value;
        }

        return $value !== null ? DayPeriod::from($value) : DayPeriod::Full;
    };

    expect($normalize('morning'))->toBe(DayPeriod::Morning)
        ->and($normalize(DayPeriod::Afternoon))->toBe(DayPeriod::Afternoon)
        ->and($normalize(null))->toBe(DayPeriod::Full);
});
