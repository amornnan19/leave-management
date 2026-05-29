<?php

namespace App\Policies;

use App\Models\LeaveBalance;
use App\Models\User;

class LeaveBalancePolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * HR-only at the model level: the admin LeaveBalanceResource is not query-scoped,
     * so Managers must not see the company-wide balance list here. The employee portal
     * exposes own-balances by overriding canViewAny() on its own scoped resource.
     */
    public function viewAny(User $user): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can view the model.
     *
     * HR sees all; an employee can see their own balance row.
     */
    public function view(User $user, LeaveBalance $leaveBalance): bool
    {
        if ($user->isHr()) {
            return true;
        }

        return $leaveBalance->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->isHr();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->isHr();
    }
}
