<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;

class LeaveRequestValidator
{
    /**
     * Validate business rules for a leave request.
     *
     * Returns an associative array of field => message for every violated rule.
     * An empty array means the request is fully valid.
     *
     * Expected $data keys: user_id, leave_type_id, start_date, end_date, total_days.
     *
     * Field mapping for inline Filament error binding:
     *  - overlap         → start_date
     *  - balance         → leave_type_id
     *  - max consecutive → end_date
     *  - min notice      → start_date
     *
     * @param  array<string, mixed>  $data
     * @param  int|null  $ignoreLeaveRequestId  When editing, pass the record's ID so its own
     *                                          dates are excluded from overlap and balance checks.
     * @return array<string, string>
     */
    public function validate(array $data, ?int $ignoreLeaveRequestId = null): array
    {
        $errors = [];

        $userId = $data['user_id'] ?? null;
        $leaveTypeId = $data['leave_type_id'] ?? null;
        $startDateRaw = $data['start_date'] ?? null;
        $endDateRaw = $data['end_date'] ?? null;
        $totalDays = isset($data['total_days']) ? (float) $data['total_days'] : null;

        // Parse dates defensively
        $startDate = $startDateRaw ? Carbon::parse($startDateRaw)->startOfDay() : null;
        $endDate = $endDateRaw ? Carbon::parse($endDateRaw)->startOfDay() : null;

        // Load leave type once for use in multiple rules
        $leaveType = $leaveTypeId ? LeaveType::find($leaveTypeId) : null;

        // Rule 1: Overlap check
        if ($userId && $startDate && $endDate) {
            $overlapError = $this->checkOverlap($userId, $startDate, $endDate, $ignoreLeaveRequestId);
            if ($overlapError !== null) {
                $errors['start_date'] = $overlapError;
            }
        }

        // Rule 2: Balance check (skipped for unpaid leave)
        if ($leaveType && $startDate && $totalDays !== null) {
            if (! isset($errors['leave_type_id'])) {
                $balanceError = $this->checkBalance($userId, $leaveType, $startDate, $totalDays, $ignoreLeaveRequestId);
                if ($balanceError !== null) {
                    $errors['leave_type_id'] = $balanceError;
                }
            }
        }

        // Rule 3: Max consecutive days
        if ($leaveType && $endDate && $totalDays !== null) {
            if (! isset($errors['end_date'])) {
                $consecutiveError = $this->checkMaxConsecutive($leaveType, $totalDays);
                if ($consecutiveError !== null) {
                    $errors['end_date'] = $consecutiveError;
                }
            }
        }

        // Rule 4: Min notice days
        if ($leaveType && $startDate) {
            $noticeError = $this->checkMinNotice($leaveType, $startDate);
            if ($noticeError !== null) {
                // Only set if overlap hasn't already claimed start_date
                if (! isset($errors['start_date'])) {
                    $errors['start_date'] = $noticeError;
                }
            }
        }

        return $errors;
    }

    /**
     * Check that the new date range does not overlap any existing Pending or Approved
     * leave request for the same user.
     *
     * Two ranges overlap when: existing.start_date <= new.end_date AND existing.end_date >= new.start_date.
     *
     * whereDate() wraps the column in DATE() so it works regardless of whether the DB
     * stores date columns as 'Y-m-d' strings or 'Y-m-d H:i:s' timestamps (SQLite).
     *
     * When editing, pass $ignoreLeaveRequestId so the request being edited does not
     * conflict with its own existing date range.
     */
    private function checkOverlap(int|string $userId, Carbon $startDate, Carbon $endDate, ?int $ignoreLeaveRequestId = null): ?string
    {
        $exists = LeaveRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', [LeaveStatus::Pending->value, LeaveStatus::Approved->value])
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->when($ignoreLeaveRequestId, fn ($q) => $q->where('id', '!=', $ignoreLeaveRequestId))
            ->exists();

        if ($exists) {
            return 'This date range overlaps an existing leave request.';
        }

        return null;
    }

    /**
     * Check that the user has sufficient leave balance for the requested type and year.
     *
     * Skipped entirely when the leave type is unpaid (is_paid === false).
     *
     * NOTE (v1): Pending requests are NOT deducted from balance — only approved requests
     * are counted.
     *
     * When editing an already-approved request ($ignoreLeaveRequestId is set), the edited
     * request's own approved days must NOT count against remaining — otherwise it would be
     * double-counted. In that case we compute used days directly (excluding the ignored ID)
     * rather than relying on the remaining_days accessor, which cannot exclude a specific row.
     */
    private function checkBalance(
        int|string|null $userId,
        LeaveType $leaveType,
        Carbon $startDate,
        float $totalDays,
        ?int $ignoreLeaveRequestId = null,
    ): ?string {
        // Exemption: unpaid leave has no quota
        if (! $leaveType->is_paid) {
            return null;
        }

        if ($userId === null) {
            return null;
        }

        $year = $startDate->year;

        $balance = LeaveBalance::query()
            ->where('user_id', $userId)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if ($balance === null) {
            return "No leave balance allocated for {$leaveType->name} in {$year}.";
        }

        if ($ignoreLeaveRequestId !== null) {
            // On edit: compute remaining manually, excluding the request being edited.
            // This avoids double-counting an already-approved request's days.
            $usedDays = (float) LeaveRequest::query()
                ->where('user_id', $userId)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', LeaveStatus::Approved)
                ->whereYear('start_date', $year)
                ->where('id', '!=', $ignoreLeaveRequestId)
                ->sum('total_days');

            $remaining = (float) $balance->entitled_days + (float) $balance->carried_over_days - $usedDays;
        } else {
            $remaining = $balance->remaining_days;
        }

        // Round to 1 decimal before comparing: remaining_days is derived from
        // decimal:1 subtraction and can land at e.g. 9.9999999 for an exact balance.
        if (round($remaining, 1) < round($totalDays, 1)) {
            $remainingLabel = number_format($remaining, 1);

            return "Insufficient balance: {$remainingLabel} day(s) remaining, {$totalDays} requested.";
        }

        return null;
    }

    /**
     * Check that total_days does not exceed max_consecutive_days for the leave type.
     *
     * Skipped when max_consecutive_days is null or 0.
     */
    private function checkMaxConsecutive(LeaveType $leaveType, float $totalDays): ?string
    {
        $max = $leaveType->max_consecutive_days;

        if ($max === null || (int) $max === 0) {
            return null;
        }

        if ($totalDays > (float) $max) {
            return "Exceeds maximum of {$max} consecutive day(s) for this leave type.";
        }

        return null;
    }

    /**
     * Check that start_date is far enough in the future to satisfy min_notice_days.
     *
     * Skipped when min_notice_days is 0.
     */
    private function checkMinNotice(LeaveType $leaveType, Carbon $startDate): ?string
    {
        $minNotice = (int) ($leaveType->min_notice_days ?? 0);

        if ($minNotice === 0) {
            return null;
        }

        $today = Carbon::today();
        $earliestStart = $today->copy()->addDays($minNotice);

        if ($startDate->lt($earliestStart)) {
            return "This leave type requires at least {$minNotice} day(s) advance notice (earliest start: {$earliestStart->toDateString()}).";
        }

        return null;
    }
}
