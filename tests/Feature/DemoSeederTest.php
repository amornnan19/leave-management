<?php

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeding is idempotent and wires demo relationships correctly', function (): void {
    // Seed twice — the second run must not duplicate any rows.
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    // 1 HR + 2 managers + 4 employees
    expect(User::count())->toBe(7)
        ->and(Department::count())->toBe(2)
        ->and(LeaveRequest::count())->toBe(3);

    // Department head points at the right manager.
    $engineering = Department::where('code', 'ENG')->firstOrFail();
    $engineeringManager = User::where('email', 'manager.eng@example.com')->firstOrFail();
    expect($engineering->head_user_id)->toBe($engineeringManager->id)
        ->and($engineeringManager->role)->toBe(UserRole::Manager);

    // Employee reports to the correct manager and sits in the correct department.
    $employee = User::where('email', 'emp.eng1@example.com')->firstOrFail();
    expect($employee->role)->toBe(UserRole::Employee)
        ->and($employee->manager_id)->toBe($engineeringManager->id)
        ->and($employee->department_id)->toBe($engineering->id);
});
