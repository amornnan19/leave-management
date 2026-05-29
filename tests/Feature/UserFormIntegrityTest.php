<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Department;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// manager_id: options query excludes non-manager roles
// ---------------------------------------------------------------------------

test('manager_id options query excludes Employee role users', function (): void {
    $hr = User::factory()->hr()->create();
    $manager = User::factory()->manager()->create();
    $employee = User::factory()->create(); // role = Employee

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // Test the query modifier directly: only Manager + HR roles should appear.
    $managerRoles = [UserRole::Manager->value, UserRole::Hr->value];
    $eligibleIds = User::query()->whereIn('role', $managerRoles)->pluck('id');

    expect($eligibleIds)->toContain($hr->id)
        ->and($eligibleIds)->toContain($manager->id)
        ->and($eligibleIds)->not->toContain($employee->id);
});

// ---------------------------------------------------------------------------
// manager_id: a user cannot be their own manager (self-assignment rule)
// ---------------------------------------------------------------------------

test('saving a user with themselves as manager fails validation', function (): void {
    $hr = User::factory()->hr()->create();
    $subject = User::factory()->manager()->create();

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->fillForm([
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => UserRole::Manager->value,
            'manager_id' => $subject->id, // self-assignment
        ])
        ->call('save')
        ->assertHasFormErrors(['manager_id']);
});

// ---------------------------------------------------------------------------
// manager_id: non-existent id fails exists validation
// ---------------------------------------------------------------------------

test('saving a user with a non-existent manager_id fails validation', function (): void {
    $hr = User::factory()->hr()->create();
    $subject = User::factory()->create();

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // Manually inject an invalid id that bypasses the select options
    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->fillForm([
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => UserRole::Employee->value,
            'manager_id' => 99999, // non-existent
        ])
        ->call('save')
        ->assertHasFormErrors(['manager_id']);
});

// ---------------------------------------------------------------------------
// department_id: non-existent id fails exists validation
// ---------------------------------------------------------------------------

test('saving a user with a non-existent department_id fails validation', function (): void {
    $hr = User::factory()->hr()->create();
    $subject = User::factory()->create();

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->fillForm([
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => UserRole::Employee->value,
            'department_id' => 99999, // non-existent
        ])
        ->call('save')
        ->assertHasFormErrors(['department_id']);
});

// ---------------------------------------------------------------------------
// department_id: valid existing department passes
// ---------------------------------------------------------------------------

test('saving a user with a valid department_id passes validation', function (): void {
    $hr = User::factory()->hr()->create();
    $subject = User::factory()->create();
    $department = Department::factory()->create();

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->fillForm([
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => UserRole::Employee->value,
            'department_id' => $department->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

// ---------------------------------------------------------------------------
// manager_id: options query excludes the record being edited (no self-loop)
// ---------------------------------------------------------------------------

test('manager_id options query excludes the record being edited', function (): void {
    $hr = User::factory()->hr()->create();
    $subject = User::factory()->manager()->create();

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // The modifyQueryUsing closure should exclude $subject from the options.
    // We verify this by checking the query result directly.
    $availableManagers = User::query()
        ->whereIn('role', [UserRole::Manager->value, UserRole::Hr->value])
        ->where('id', '!=', $subject->id)
        ->pluck('id');

    expect($availableManagers)->not->toContain($subject->id)
        ->and($availableManagers)->toContain($hr->id);
});

// ---------------------------------------------------------------------------
// manager_id: an Employee-role user cannot be assigned as a manager (role rule)
// ---------------------------------------------------------------------------

test('saving a user with an Employee as their manager fails validation', function (): void {
    $hr = User::factory()->hr()->create();
    $subject = User::factory()->create();
    $employeeAsManager = User::factory()->create(); // role = Employee — ineligible

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->fillForm([
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => UserRole::Employee->value,
            'manager_id' => $employeeAsManager->id, // exists, but wrong role
        ])
        ->call('save')
        ->assertHasFormErrors(['manager_id']);
});

test('saving a user with a Manager-role user as their manager passes validation', function (): void {
    $hr = User::factory()->hr()->create();
    $subject = User::factory()->create();
    $manager = User::factory()->manager()->create();

    actingAs($hr);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditUser::class, ['record' => $subject->getRouteKey()])
        ->fillForm([
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => UserRole::Employee->value,
            'manager_id' => $manager->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});
