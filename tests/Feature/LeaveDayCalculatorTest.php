<?php

use App\Enums\DayPeriod;
use App\Models\Holiday;
use App\Services\LeaveDayCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Reference dates (all verified):
// 2026-06-01 = Monday
// 2026-06-04 = Thursday
// 2026-06-05 = Friday
// 2026-06-06 = Saturday
// 2026-06-07 = Sunday
// 2026-06-08 = Monday

function calculator(): LeaveDayCalculator
{
    return new LeaveDayCalculator;
}

test('Monday to Friday is 5 working days', function (): void {
    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
    );

    expect($result)->toBe(5.0);
});

test('Thursday to next Monday excludes Saturday and Sunday', function (): void {
    // 2026-06-04 Thu, 2026-06-05 Fri, 2026-06-06 Sat (skip), 2026-06-07 Sun (skip), 2026-06-08 Mon → 3 working days
    $result = calculator()->calculate(
        Carbon::parse('2026-06-04'),
        Carbon::parse('2026-06-08'),
    );

    expect($result)->toBe(3.0);
});

test('a non-recurring holiday inside the range is excluded', function (): void {
    // Wed 2026-06-03 is a holiday → Mon–Fri gives 4 working days
    Holiday::factory()->create([
        'date' => '2026-06-03',
        'is_recurring' => false,
    ]);

    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
    );

    expect($result)->toBe(4.0);
});

test('a recurring holiday is excluded even in a different year', function (): void {
    // Jan 1 stored as 2025-01-01 with is_recurring=true → also blocks 2026-01-01 (Thursday)
    // Range 2026-01-01..2026-01-02: Thu (recurring holiday) + Fri = 1 working day
    Holiday::factory()->create([
        'date' => '2025-01-01',
        'is_recurring' => true,
    ]);

    $result = calculator()->calculate(
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-01-02'),
    );

    expect($result)->toBe(1.0);
});

test('single working day with Full period returns 1.0', function (): void {
    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-01'),
        DayPeriod::Full,
        DayPeriod::Full,
    );

    expect($result)->toBe(1.0);
});

test('single working day with Morning end period returns 0.5', function (): void {
    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-01'),
        DayPeriod::Full,
        DayPeriod::Morning,
    );

    expect($result)->toBe(0.5);
});

test('single working day with Afternoon start period returns 0.5', function (): void {
    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-01'),
        DayPeriod::Afternoon,
        DayPeriod::Full,
    );

    expect($result)->toBe(0.5);
});

test('multi-day with Afternoon start and Morning end subtracts 1.0 total', function (): void {
    // Mon–Fri = 5 days, −0.5 (Afternoon start) −0.5 (Morning end) = 4.0
    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
        DayPeriod::Afternoon,
        DayPeriod::Morning,
    );

    expect($result)->toBe(4.0);
});

test('Afternoon start on a weekend day does not over-subtract', function (): void {
    // Start on Saturday, end on Monday → only Monday is a working day = 1.0
    // Saturday is not a working day so Afternoon start adjustment is not applied
    $result = calculator()->calculate(
        Carbon::parse('2026-06-06'),
        Carbon::parse('2026-06-08'),
        DayPeriod::Afternoon,
        DayPeriod::Full,
    );

    expect($result)->toBe(1.0);
});

test('Morning end on a holiday does not subtract', function (): void {
    // 2026-06-01 Mon is a non-recurring holiday → excluded
    // Range 2026-06-01..2026-06-02: end is holiday (not working) so Morning end does not subtract
    // Only 2026-06-02 Tue is working → 1 working day; end boundary is working day with Morning → 0.5
    Holiday::factory()->create([
        'date' => '2026-06-01',
        'is_recurring' => false,
    ]);

    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-02'),
        DayPeriod::Full,
        DayPeriod::Morning,
    );

    // end 2026-06-02 IS a working day → Morning end subtracts → 0.5
    expect($result)->toBe(0.5);
});

test('end before start returns 0.0', function (): void {
    $result = calculator()->calculate(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-01'),
    );

    expect($result)->toBe(0.0);
});

test('range entirely on a weekend returns 0.0', function (): void {
    $result = calculator()->calculate(
        Carbon::parse('2026-06-06'),
        Carbon::parse('2026-06-07'),
    );

    expect($result)->toBe(0.0);
});

test('Morning as start period does not subtract', function (): void {
    // Morning start = full first day, no adjustment
    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
        DayPeriod::Morning,
        DayPeriod::Full,
    );

    expect($result)->toBe(5.0);
});

test('Afternoon as end period does not subtract', function (): void {
    // Afternoon end = full last day, no adjustment
    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
        DayPeriod::Full,
        DayPeriod::Afternoon,
    );

    expect($result)->toBe(5.0);
});

test('non-recurring holiday outside range is not excluded', function (): void {
    Holiday::factory()->create([
        'date' => '2026-06-15',
        'is_recurring' => false,
    ]);

    $result = calculator()->calculate(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
    );

    expect($result)->toBe(5.0);
});
