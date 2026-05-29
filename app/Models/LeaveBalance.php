<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use Database\Factories\LeaveBalanceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'carried_over_days',
    ];

    protected function casts(): array
    {
        return [
            'entitled_days' => 'decimal:1',
            'carried_over_days' => 'decimal:1',
            'year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Total approved leave days consumed for this balance's user, leave type, and year.
     *
     * Year bucketing: a leave request is counted entirely against the year of its
     * start_date. A request that spans a year boundary (e.g. 30 Dec – 2 Jan) is NOT
     * split proportionally; all of its total_days are attributed to the start year.
     *
     * NOTE: This accessor triggers a database query. Avoid calling in loops;
     * eager-compute used_days when listing balances in bulk.
     *
     * @return Attribute<float, never>
     */
    protected function usedDays(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                return (float) LeaveRequest::query()
                    ->where('user_id', $this->user_id)
                    ->where('leave_type_id', $this->leave_type_id)
                    ->where('status', LeaveStatus::Approved)
                    ->whereYear('start_date', $this->year)
                    ->sum('total_days');
            }
        );
    }

    /**
     * Remaining days = entitled + carried over - used.
     *
     * @return Attribute<float, never>
     */
    protected function remainingDays(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->entitled_days + (float) $this->carried_over_days - $this->used_days
        );
    }
}
