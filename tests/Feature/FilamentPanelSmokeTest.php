<?php

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Filament\Resources\LeaveBalances\Pages\CreateLeaveBalance;
use App\Filament\Resources\LeaveBalances\Pages\ListLeaveBalances;
use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Resources\LeaveTypes\Pages\CreateLeaveType;
use App\Filament\Resources\LeaveTypes\Pages\ListLeaveTypes;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function hrUser(): User
{
    return User::factory()->hr()->create();
}

// ---------------------------------------------------------------------------
// List pages
// ---------------------------------------------------------------------------

test('departments list page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(ListDepartments::class)
        ->assertOk();
});

test('holidays list page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(ListHolidays::class)
        ->assertOk();
});

test('leave-balances list page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(ListLeaveBalances::class)
        ->assertOk();
});

test('leave-requests list page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(ListLeaveRequests::class)
        ->assertOk();
});

test('leave-types list page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(ListLeaveTypes::class)
        ->assertOk();
});

test('users list page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(ListUsers::class)
        ->assertOk();
});

// ---------------------------------------------------------------------------
// Create pages — these exercise the form schema closures
// (the original bug: wrong Get namespace threw on render when the closure was evaluated)
// ---------------------------------------------------------------------------

test('departments create page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(CreateDepartment::class)
        ->assertOk();
});

test('holidays create page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(CreateHoliday::class)
        ->assertOk();
});

test('leave-balances create page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(CreateLeaveBalance::class)
        ->assertOk();
});

test('leave-requests create page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(CreateLeaveRequest::class)
        ->assertOk();
});

test('leave-types create page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(CreateLeaveType::class)
        ->assertOk();
});

test('users create page renders without error', function (): void {
    actingAs(hrUser());

    Livewire::test(CreateUser::class)
        ->assertOk();
});
