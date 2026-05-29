<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;

class LeaveBalanceGenerator
{
    /**
     * Ensure a LeaveBalance row exists for every User × every active+paid LeaveType
     * combination for the given year.
     *
     * Uses firstOrCreate keyed on (user_id, leave_type_id, year) so the operation is
     * fully idempotent — existing rows with manually-adjusted entitled_days are never
     * overwritten. Unpaid leave types are skipped entirely.
     *
     * @return array{created: int, skipped: int, users: int, leave_types: int}
     */
    public function generateForYear(int $year): array
    {
        $users = User::all(['id']);
        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->where('is_paid', true)
            ->get(['id', 'default_days_per_year']);

        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            foreach ($leaveTypes as $leaveType) {
                $balance = LeaveBalance::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $year,
                    ],
                    [
                        'entitled_days' => $leaveType->default_days_per_year,
                        'carried_over_days' => 0,
                    ]
                );

                if ($balance->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'users' => $users->count(),
            'leave_types' => $leaveTypes->count(),
        ];
    }
}
