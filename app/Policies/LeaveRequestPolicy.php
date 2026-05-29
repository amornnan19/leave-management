<?php

namespace App\Policies;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Row-level visibility is enforced by each resource's getEloquentQuery.
     * This gate just controls panel access; the query scoping does the real work.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * HR → always; owner → own record; manager → own or subordinate's.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isHr()) {
            return true;
        }

        if ($leaveRequest->user_id === $user->id) {
            return true;
        }

        if ($user->isManager()) {
            return $leaveRequest->user?->manager_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * Employees create via the portal (user_id forced to self); HR/Manager via admin.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isHr()) {
            return true;
        }

        if ($user->isManager()) {
            return $leaveRequest->user_id === $user->id
                || $leaveRequest->user?->manager_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can bulk-delete models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can approve or reject the leave request.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isHr()) {
            return true;
        }

        if ($user->isManager()) {
            return $leaveRequest->user_id === $user->id
                || $leaveRequest->user?->manager_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the leave request.
     *
     * HR can always cancel. An employee (including Manager/HR acting on their own)
     * can cancel their own Pending request.
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->isHr()) {
            return true;
        }

        return $leaveRequest->user_id === $user->id
            && $leaveRequest->status === LeaveStatus::Pending;
    }
}
