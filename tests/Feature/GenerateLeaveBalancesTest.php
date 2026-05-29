<?php

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function generator(): LeaveBalanceGenerator
{
    return new LeaveBalanceGenerator;
}

// ---------------------------------------------------------------------------
// Core generation
// ---------------------------------------------------------------------------

test('generates a balance for every user × every active and paid leave type', function (): void {
    $users = User::factory()->count(3)->create();
    LeaveType::factory()->count(2)->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    $stats = generator()->generateForYear(2026);

    expect(LeaveBalance::count())->toBe(6)
        ->and($stats['created'])->toBe(6)
        ->and($stats['skipped'])->toBe(0)
        ->and($stats['users'])->toBe(3)
        ->and($stats['leave_types'])->toBe(2);
});

test('generated balances have entitled_days equal to default_days_per_year', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 15.0,
    ]);

    generator()->generateForYear(2026);

    $balance = LeaveBalance::first();

    expect((float) $balance->entitled_days)->toBe(15.0)
        ->and((float) $balance->carried_over_days)->toBe(0.0);
});

test('generated balances are created for the given year', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    generator()->generateForYear(2025);

    $balance = LeaveBalance::first();

    expect($balance->year)->toBe(2025);
});

test('uses current year when no year argument is provided via artisan command', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    $this->artisan('leave:generate-balances')->assertSuccessful();

    expect(LeaveBalance::where('year', now()->year)->count())->toBe(1);
});

test('respects year argument passed to artisan command', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    $this->artisan('leave:generate-balances', ['year' => 2027])->assertSuccessful();

    expect(LeaveBalance::where('year', 2027)->count())->toBe(1)
        ->and(LeaveBalance::where('year', now()->year)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Exclusions
// ---------------------------------------------------------------------------

test('does not create balances for unpaid leave types', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => false,
        'is_active' => true,
        'default_days_per_year' => 0.0,
    ]);

    $stats = generator()->generateForYear(2026);

    expect(LeaveBalance::count())->toBe(0)
        ->and($stats['created'])->toBe(0)
        ->and($stats['leave_types'])->toBe(0);
});

test('does not create balances for inactive leave types', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => false,
        'default_days_per_year' => 10.0,
    ]);

    $stats = generator()->generateForYear(2026);

    expect(LeaveBalance::count())->toBe(0)
        ->and($stats['created'])->toBe(0)
        ->and($stats['leave_types'])->toBe(0);
});

test('does not create balances for inactive unpaid leave types', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => false,
        'is_active' => false,
        'default_days_per_year' => 0.0,
    ]);

    $stats = generator()->generateForYear(2026);

    expect(LeaveBalance::count())->toBe(0);
});

test('only generates for active paid types when a mix exists', function (): void {
    User::factory()->create();

    LeaveType::factory()->create(['is_paid' => true, 'is_active' => true, 'default_days_per_year' => 10.0]);
    LeaveType::factory()->create(['is_paid' => false, 'is_active' => true, 'default_days_per_year' => 0.0]);
    LeaveType::factory()->create(['is_paid' => true, 'is_active' => false, 'default_days_per_year' => 5.0]);

    $stats = generator()->generateForYear(2026);

    expect(LeaveBalance::count())->toBe(1)
        ->and($stats['leave_types'])->toBe(1);
});

// ---------------------------------------------------------------------------
// Idempotency
// ---------------------------------------------------------------------------

test('running twice does not create duplicate rows', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    generator()->generateForYear(2026);
    generator()->generateForYear(2026);

    expect(LeaveBalance::count())->toBe(1);
});

test('running twice counts first run as created and second as skipped', function (): void {
    User::factory()->create();
    LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    $first = generator()->generateForYear(2026);
    $second = generator()->generateForYear(2026);

    expect($first['created'])->toBe(1)
        ->and($first['skipped'])->toBe(0)
        ->and($second['created'])->toBe(0)
        ->and($second['skipped'])->toBe(1);
});

test('does not overwrite an existing balance with manually adjusted entitled_days', function (): void {
    $user = User::factory()->create();
    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    // Manually create a balance with a custom entitled_days value
    LeaveBalance::create([
        'user_id' => $user->id,
        'leave_type_id' => $leaveType->id,
        'year' => 2026,
        'entitled_days' => 99.0,
        'carried_over_days' => 5.0,
    ]);

    generator()->generateForYear(2026);

    $balance = LeaveBalance::first();

    // The manually-set values must be preserved
    expect((float) $balance->entitled_days)->toBe(99.0)
        ->and((float) $balance->carried_over_days)->toBe(5.0);
});

// ---------------------------------------------------------------------------
// Artisan command output
// ---------------------------------------------------------------------------

test('artisan command prints a clear summary line', function (): void {
    User::factory()->count(2)->create();
    LeaveType::factory()->count(3)->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    $this->artisan('leave:generate-balances', ['year' => 2026])
        ->expectsOutputToContain('Generated 6 balance(s) for 2 user(s) × 3 paid leave type(s) for 2026 (0 already existed, skipped).')
        ->assertSuccessful();
});

test('artisan command reports skipped count on second run', function (): void {
    User::factory()->count(2)->create();
    LeaveType::factory()->count(3)->create([
        'is_paid' => true,
        'is_active' => true,
        'default_days_per_year' => 10.0,
    ]);

    $this->artisan('leave:generate-balances', ['year' => 2026])->assertSuccessful();

    $this->artisan('leave:generate-balances', ['year' => 2026])
        ->expectsOutputToContain('Generated 0 balance(s) for 2 user(s) × 3 paid leave type(s) for 2026 (6 already existed, skipped).')
        ->assertSuccessful();
});
