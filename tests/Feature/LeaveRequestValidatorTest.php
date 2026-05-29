<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveRequestValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Reference dates (all verified):
// 2026-06-15 = Monday
// 2026-06-16 = Tuesday
// 2026-06-17 = Wednesday
// 2026-06-22 = Monday (one week later)
// 2026-12-01 = Tuesday (far future — safe for min notice)

function leaveValidator(): LeaveRequestValidator
{
    return new LeaveRequestValidator;
}

/**
 * Build a base $data array. Uses unpaid leave + no constraints by default
 * so individual rules can be isolated cleanly.
 *
 * @return array<string, mixed>
 */
function makeRequestData(User $user, LeaveType $leaveType, string $start = '2026-06-22', string $end = '2026-06-22', float $totalDays = 1.0): array
{
    return [
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => $start,
        'end_date' => $end,
        'total_days' => $totalDays,
    ];
}

// ---------------------------------------------------------------------------
// Fully-valid request
// ---------------------------------------------------------------------------

test('fully valid request returns empty array', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'max_consecutive_days' => null,
        'min_notice_days' => 0,
    ]);
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2026,
        'entitled_days' => 10.0,
        'carried_over_days' => 0.0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);

    expect(leaveValidator()->validate($data))->toBe([]);
});

// ---------------------------------------------------------------------------
// Rule 1: Overlap
// ---------------------------------------------------------------------------

test('overlap: pending request with identical dates blocks new request', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['is_paid' => false, 'min_notice_days' => 0]);

    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-17',
        'status' => LeaveStatus::Pending,
        'total_days' => 3.0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-15', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->toHaveKey('start_date')
        ->and($errors['start_date'])->toContain('overlaps');
});

test('overlap: approved request overlapping new range blocks new request', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['is_paid' => false, 'min_notice_days' => 0]);

    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-17',
        'status' => LeaveStatus::Approved,
        'total_days' => 3.0,
    ]);

    // New request ends inside existing range
    $data = makeRequestData($user, $leaveType, '2026-06-14', '2026-06-16', 2.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->toHaveKey('start_date');
});

test('overlap: non-overlapping date range is allowed', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['is_paid' => false, 'min_notice_days' => 0]);

    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-17',
        'status' => LeaveStatus::Pending,
        'total_days' => 3.0,
    ]);

    // New request starts after existing ends
    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});

test('overlap: rejected request does not block new overlapping request', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['is_paid' => false, 'min_notice_days' => 0]);

    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-17',
        'status' => LeaveStatus::Rejected,
        'total_days' => 3.0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-17', 3.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});

test('overlap: cancelled request does not block new overlapping request', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['is_paid' => false, 'min_notice_days' => 0]);

    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-17',
        'status' => LeaveStatus::Cancelled,
        'total_days' => 3.0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-17', 3.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});

test('overlap: different user with overlapping dates is not blocked', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['is_paid' => false, 'min_notice_days' => 0]);

    LeaveRequest::factory()->create([
        'user_id' => $otherUser->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-17',
        'status' => LeaveStatus::Pending,
        'total_days' => 3.0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-17', 3.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});

// ---------------------------------------------------------------------------
// Rule 2: Balance
// ---------------------------------------------------------------------------

test('balance: paid type with no balance row is blocked', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'min_notice_days' => 0,
    ]);
    // No LeaveBalance created

    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->toHaveKey('leave_type_id')
        ->and($errors['leave_type_id'])->toContain('No leave balance allocated');
});

test('balance: paid type with sufficient remaining days passes', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'min_notice_days' => 0,
    ]);
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2026,
        'entitled_days' => 10.0,
        'carried_over_days' => 0.0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('leave_type_id');
});

test('balance: remaining exactly equals requested passes', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'min_notice_days' => 0,
    ]);
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2026,
        'entitled_days' => 1.0,
        'carried_over_days' => 0.0,
    ]);

    // Requesting exactly the remaining 1.0 day
    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('leave_type_id');
});

test('balance: fractional remaining exactly equals fractional request passes', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'min_notice_days' => 0,
    ]);
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2026,
        'entitled_days' => 2.0,
        'carried_over_days' => 0.5,
    ]);

    // Remaining 2.5, requesting exactly 2.5 (half-day boundary)
    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-24', 2.5);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('leave_type_id');
});

test('balance: paid type with insufficient remaining days is blocked', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'min_notice_days' => 0,
    ]);
    // Only 0.5 days remaining
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2026,
        'entitled_days' => 0.5,
        'carried_over_days' => 0.0,
    ]);

    // Requesting 1 day
    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->toHaveKey('leave_type_id')
        ->and($errors['leave_type_id'])->toContain('Insufficient balance');
});

test('balance: unpaid type with no balance row is allowed (exemption)', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'min_notice_days' => 0,
    ]);
    // No LeaveBalance — should be exempt

    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('leave_type_id');
});

test('balance: year bucket uses start_date year not current year', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'min_notice_days' => 0,
    ]);
    // Balance for 2027, but request starts in 2026
    LeaveBalance::factory()->create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2027,
        'entitled_days' => 10.0,
        'carried_over_days' => 0.0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-22', '2026-06-22', 1.0);
    $errors = leaveValidator()->validate($data);

    // 2026 balance does not exist → blocked
    expect($errors)->toHaveKey('leave_type_id')
        ->and($errors['leave_type_id'])->toContain('No leave balance allocated');
});

// ---------------------------------------------------------------------------
// Rule 3: Max consecutive days
// ---------------------------------------------------------------------------

test('max consecutive: total_days over limit is blocked', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => 3,
        'min_notice_days' => 0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-19', 5.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->toHaveKey('end_date')
        ->and($errors['end_date'])->toContain('Exceeds maximum');
});

test('max consecutive: total_days equal to limit passes', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => 3,
        'min_notice_days' => 0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-17', 3.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('end_date');
});

test('max consecutive: total_days within limit passes', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => 5,
        'min_notice_days' => 0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-16', 2.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('end_date');
});

test('max consecutive: null limit imposes no restriction', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => null,
        'min_notice_days' => 0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-30', 12.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('end_date');
});

test('max consecutive: zero limit imposes no restriction', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => 0,
        'min_notice_days' => 0,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-06-15', '2026-06-30', 12.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('end_date');
});

// ---------------------------------------------------------------------------
// Rule 4: Min notice days
// ---------------------------------------------------------------------------

test('min notice: start_date too soon for a type with min_notice_days=3 is blocked', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => null,
        'min_notice_days' => 3,
    ]);

    // today + 1 day ahead — not enough for min_notice_days=3
    $tooSoon = Carbon::today()->addDay()->toDateString();

    $data = makeRequestData($user, $leaveType, $tooSoon, $tooSoon, 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->toHaveKey('start_date')
        ->and($errors['start_date'])->toContain('advance notice');
});

test('min notice: start_date exactly at min_notice boundary passes', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => null,
        'min_notice_days' => 3,
    ]);

    // today + 3 = earliest allowed start
    $exactStart = Carbon::today()->addDays(3)->toDateString();

    $data = makeRequestData($user, $leaveType, $exactStart, $exactStart, 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});

test('min notice: start_date far in the future passes', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => null,
        'min_notice_days' => 3,
    ]);

    $data = makeRequestData($user, $leaveType, '2026-12-01', '2026-12-01', 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});

test('min notice: type with min_notice_days=0 imposes no restriction', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'max_consecutive_days' => null,
        'min_notice_days' => 0,
    ]);

    // Even today is allowed
    $today = Carbon::today()->toDateString();

    $data = makeRequestData($user, $leaveType, $today, $today, 1.0);
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});

// ---------------------------------------------------------------------------
// Defensive: missing input keys
// ---------------------------------------------------------------------------

test('missing start_date skips overlap and min notice checks gracefully', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create(['is_paid' => false, 'min_notice_days' => 3]);

    $data = [
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        // start_date intentionally omitted
        'end_date' => '2026-06-22',
        'total_days' => 1.0,
    ];

    // Should not throw; skips rules that need start_date
    $errors = leaveValidator()->validate($data);

    expect($errors)->not->toHaveKey('start_date');
});
