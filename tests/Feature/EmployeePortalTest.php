<?php

use App\Enums\LeaveStatus;
use App\Filament\Employee\Resources\MyLeaveBalances\MyLeaveBalanceResource;
use App\Filament\Employee\Resources\MyLeaveBalances\Pages\ListMyLeaveBalances;
use App\Filament\Employee\Resources\MyLeaveRequests\MyLeaveRequestResource;
use App\Filament\Employee\Resources\MyLeaveRequests\Pages\CreateMyLeaveRequest;
use App\Filament\Employee\Resources\MyLeaveRequests\Pages\ListMyLeaveRequests;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Access control — /portal vs /admin
// ---------------------------------------------------------------------------

test('employee can access the portal', function (): void {
    $employee = User::factory()->create(); // default role = Employee

    actingAs($employee);

    // Filament serves the panel page directly (200) when the user is already authenticated.
    $this->get('/portal')->assertOk();
});

test('unauthenticated user is redirected to portal login not admin login', function (): void {
    $this->get('/portal')->assertRedirectToRoute('filament.employee.auth.login');
});

test('employee is blocked from the admin panel', function (): void {
    $employee = User::factory()->create();

    actingAs($employee);

    $this->get('/admin')->assertForbidden();
});

test('hr user can access portal', function (): void {
    $hr = User::factory()->hr()->create();

    actingAs($hr);

    $this->get('/portal')->assertOk();
});

test('hr user can access admin panel', function (): void {
    $hr = User::factory()->hr()->create();

    actingAs($hr);

    $this->get('/admin')->assertOk();
});

test('manager can access portal', function (): void {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    $this->get('/portal')->assertOk();
});

test('manager can access admin panel', function (): void {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    $this->get('/admin')->assertOk();
});

// ---------------------------------------------------------------------------
// Query scoping — employee only sees their own requests
// ---------------------------------------------------------------------------

test('employee portal list query only returns own leave requests', function (): void {
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();

    $ownRequest = LeaveRequest::factory()->create(['user_id' => $employeeA->id]);
    $otherRequest = LeaveRequest::factory()->create(['user_id' => $employeeB->id]);

    actingAs($employeeA);

    $query = MyLeaveRequestResource::getEloquentQuery();

    expect($query->pluck('id'))->toContain($ownRequest->id)
        ->and($query->pluck('id'))->not->toContain($otherRequest->id);
});

test('employee portal balance query only returns own balance rows', function (): void {
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();

    $ownBalance = LeaveBalance::factory()->create(['user_id' => $employeeA->id]);
    $otherBalance = LeaveBalance::factory()->create(['user_id' => $employeeB->id]);

    actingAs($employeeA);

    $query = MyLeaveBalanceResource::getEloquentQuery();

    expect($query->pluck('id'))->toContain($ownBalance->id)
        ->and($query->pluck('id'))->not->toContain($otherBalance->id);
});

// ---------------------------------------------------------------------------
// Forced ownership — user_id is always the authenticated user
// ---------------------------------------------------------------------------

test('portal create forces user_id to authenticated employee even if another id is injected', function (): void {
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();

    $leaveType = LeaveType::factory()->create([
        'is_paid' => false,
        'min_notice_days' => 0,
        'max_consecutive_days' => null,
        'requires_attachment' => false,
    ]);

    actingAs($employeeA);
    Filament::setCurrentPanel(Filament::getPanel('employee'));

    // Attempt to submit with employeeB's user_id in the data
    Livewire::test(CreateMyLeaveRequest::class)
        ->fillForm([
            'user_id' => $employeeB->id, // injected — should be overwritten
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-22',
            'start_period' => 'full',
            'end_period' => 'full',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $saved = LeaveRequest::latest('id')->first();

    expect($saved->user_id)->toBe($employeeA->id)
        ->and($saved->user_id)->not->toBe($employeeB->id);
});

// ---------------------------------------------------------------------------
// Business rules still apply on portal create
// ---------------------------------------------------------------------------

test('portal create blocks submission when no balance row exists for a paid leave type', function (): void {
    $employee = User::factory()->create();

    $leaveType = LeaveType::factory()->create([
        'is_paid' => true,
        'min_notice_days' => 0,
        'max_consecutive_days' => null,
        'requires_attachment' => false,
    ]);

    // No LeaveBalance created → should be blocked

    actingAs($employee);
    Filament::setCurrentPanel(Filament::getPanel('employee'));

    Livewire::test(CreateMyLeaveRequest::class)
        ->fillForm([
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-22',
            'start_period' => 'full',
            'end_period' => 'full',
        ])
        ->call('create')
        ->assertHasFormErrors(['leave_type_id']);
});

// ---------------------------------------------------------------------------
// Cancel action
// ---------------------------------------------------------------------------

test('employee can cancel their own pending request via policy', function (): void {
    $employee = User::factory()->create();
    $request = LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'status' => LeaveStatus::Pending,
    ]);

    expect($employee->can('cancel', $request))->toBeTrue();
});

test('employee cannot cancel an approved request via policy', function (): void {
    $employee = User::factory()->create();
    $request = LeaveRequest::factory()->approved()->create(['user_id' => $employee->id]);

    expect($employee->can('cancel', $request))->toBeFalse();
});

test('employee cannot cancel another employees pending request via policy', function (): void {
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();

    $request = LeaveRequest::factory()->create([
        'user_id' => $employeeB->id,
        'status' => LeaveStatus::Pending,
    ]);

    expect($employeeA->can('cancel', $request))->toBeFalse();
});

test('hr can cancel any pending request via policy', function (): void {
    $hr = User::factory()->hr()->create();
    $request = LeaveRequest::factory()->create(['status' => LeaveStatus::Pending]);

    expect($hr->can('cancel', $request))->toBeTrue();
});

test('cancelling a pending request sets status to Cancelled', function (): void {
    $employee = User::factory()->create();
    $request = LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'status' => LeaveStatus::Pending,
    ]);

    $request->update(['status' => LeaveStatus::Cancelled]);
    $request->refresh();

    expect($request->status)->toBe(LeaveStatus::Cancelled);
});

// ---------------------------------------------------------------------------
// Portal smoke tests — pages render without error (guards the panel wiring and
// the v5 Get namespace used in the form schema closures)
// ---------------------------------------------------------------------------

test('portal list my leave requests page renders for an employee', function (): void {
    $employee = User::factory()->create();

    actingAs($employee);
    Filament::setCurrentPanel(Filament::getPanel('employee'));

    Livewire::test(ListMyLeaveRequests::class)
        ->assertOk();
});

test('portal create my leave request page renders for an employee', function (): void {
    $employee = User::factory()->create();

    actingAs($employee);
    Filament::setCurrentPanel(Filament::getPanel('employee'));

    Livewire::test(CreateMyLeaveRequest::class)
        ->assertOk();
});

test('portal list my leave balances page renders for an employee', function (): void {
    $employee = User::factory()->create();

    actingAs($employee);
    Filament::setCurrentPanel(Filament::getPanel('employee'));

    Livewire::test(ListMyLeaveBalances::class)
        ->assertOk();
});

// ---------------------------------------------------------------------------
// Regression: relaxing portal access must not expose admin balances to managers
// ---------------------------------------------------------------------------

test('manager cannot list leave balances in the admin panel', function (): void {
    $manager = User::factory()->manager()->create();

    actingAs($manager);

    // The admin LeaveBalanceResource is not query-scoped, so a Manager must not
    // be able to reach its (company-wide) list page.
    $this->get('/admin/leave-balances')->assertForbidden();
});

test('hr can list leave balances in the admin panel', function (): void {
    $hr = User::factory()->hr()->create();

    actingAs($hr);

    $this->get('/admin/leave-balances')->assertOk();
});
