<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveRequestNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper: build a pending request with a fixed leave type name and date
// ---------------------------------------------------------------------------

function makePendingRequest(User $owner, string $leaveTypeName = 'Annual Leave'): LeaveRequest
{
    $leaveType = LeaveType::factory()->create(['name' => $leaveTypeName]);

    return LeaveRequest::factory()->create([
        'user_id' => $owner->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-08',
        'end_date' => '2026-06-10',
        'total_days' => 3.0,
        'status' => LeaveStatus::Pending,
    ]);
}

// ---------------------------------------------------------------------------
// Service: approved()
// ---------------------------------------------------------------------------

test('approved sends exactly one database notification to the request owner', function (): void {
    $owner = User::factory()->create();
    $request = makePendingRequest($owner, 'Annual Leave');

    $request->update([
        'status' => LeaveStatus::Approved,
        'approver_id' => User::factory()->hr()->create()->id,
        'approved_at' => now(),
    ]);
    $request->refresh();

    app(LeaveRequestNotifier::class)->approved($request);

    expect($owner->fresh()->notifications()->count())->toBe(1);
});

test('approved notification data contains "approved" and the leave type name', function (): void {
    $owner = User::factory()->create();
    $request = makePendingRequest($owner, 'Annual Leave');

    $request->update([
        'status' => LeaveStatus::Approved,
        'approver_id' => User::factory()->hr()->create()->id,
        'approved_at' => now(),
    ]);
    $request->refresh();

    app(LeaveRequestNotifier::class)->approved($request);

    $notification = $owner->fresh()->notifications()->first();
    $data = $notification->data;

    expect(strtolower($data['title']))->toContain('approved')
        ->and($data['body'])->toContain('Annual Leave')
        ->and($data['body'])->toContain('Jun 8, 2026')
        ->and($data['body'])->toContain('3');
});

// ---------------------------------------------------------------------------
// Service: rejected()
// ---------------------------------------------------------------------------

test('rejected sends exactly one database notification to the request owner', function (): void {
    $owner = User::factory()->create();
    $request = makePendingRequest($owner, 'Sick Leave');

    $request->update([
        'status' => LeaveStatus::Rejected,
        'approver_id' => User::factory()->hr()->create()->id,
        'approved_at' => now(),
        'rejection_reason' => 'Insufficient notice period.',
    ]);
    $request->refresh();

    app(LeaveRequestNotifier::class)->rejected($request);

    expect($owner->fresh()->notifications()->count())->toBe(1);
});

test('rejected notification data contains "rejected" and the rejection reason', function (): void {
    $owner = User::factory()->create();
    $request = makePendingRequest($owner, 'Sick Leave');

    $rejectionReason = 'Insufficient notice period.';

    $request->update([
        'status' => LeaveStatus::Rejected,
        'approver_id' => User::factory()->hr()->create()->id,
        'approved_at' => now(),
        'rejection_reason' => $rejectionReason,
    ]);
    $request->refresh();

    app(LeaveRequestNotifier::class)->rejected($request);

    $notification = $owner->fresh()->notifications()->first();
    $data = $notification->data;

    expect(strtolower($data['title']))->toContain('rejected')
        ->and($data['body'])->toContain($rejectionReason);
});

// ---------------------------------------------------------------------------
// Actor (HR/approver) does NOT receive a database notification
// ---------------------------------------------------------------------------

test('the approving actor does not receive a database notification', function (): void {
    $owner = User::factory()->create();
    $actor = User::factory()->hr()->create();
    $request = makePendingRequest($owner, 'Annual Leave');

    $request->update([
        'status' => LeaveStatus::Approved,
        'approver_id' => $actor->id,
        'approved_at' => now(),
    ]);
    $request->refresh();

    app(LeaveRequestNotifier::class)->approved($request);

    expect($actor->fresh()->notifications()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Defensive: null user guard does not throw
// ---------------------------------------------------------------------------

test('approved does not throw when the leave request has no user', function (): void {
    $owner = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['name' => 'Annual Leave']);

    $request = LeaveRequest::factory()->create([
        'user_id' => $owner->id,
        'leave_type_id' => $leaveType->id,
        'status' => LeaveStatus::Approved,
    ]);

    // Force the relationship to return null
    $request->setRelation('user', null);

    expect(fn () => app(LeaveRequestNotifier::class)->approved($request))->not->toThrow(Throwable::class);
});

test('rejected does not throw when the leave request has no user', function (): void {
    $owner = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['name' => 'Sick Leave']);

    $request = LeaveRequest::factory()->create([
        'user_id' => $owner->id,
        'leave_type_id' => $leaveType->id,
        'status' => LeaveStatus::Rejected,
        'rejection_reason' => 'Reason here',
    ]);

    $request->setRelation('user', null);

    expect(fn () => app(LeaveRequestNotifier::class)->rejected($request))->not->toThrow(Throwable::class);
});

// ---------------------------------------------------------------------------
// Regression: delivery must be synchronous even on the database queue
// (the app runs on QUEUE_CONNECTION=database without a guaranteed worker)
// ---------------------------------------------------------------------------

test('notification is delivered synchronously even when the queue is database', function (): void {
    config(['queue.default' => 'database']);

    $owner = User::factory()->create();
    $request = makePendingRequest($owner, 'Annual Leave');

    $request->update([
        'status' => LeaveStatus::Approved,
        'approver_id' => User::factory()->hr()->create()->id,
        'approved_at' => now(),
    ]);

    app(LeaveRequestNotifier::class)->approved($request);

    // notifyNow() bypasses the queue: the row must exist immediately and nothing
    // should be parked in the jobs table. (A regression to sendToDatabase() would
    // queue the DatabaseNotification here, leaving 0 notifications + 1 job.)
    expect($owner->fresh()->notifications()->count())->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(0);
});
