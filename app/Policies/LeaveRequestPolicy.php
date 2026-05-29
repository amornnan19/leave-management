<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isHr() || $user->isManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
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
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isHr() || $user->isManager();
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
}
