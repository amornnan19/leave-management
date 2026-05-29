<?php

namespace App\Models;

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'start_period',
        'end_period',
        'total_days',
        'reason',
        'status',
        'approver_id',
        'approved_at',
        'rejection_reason',
        'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_period' => DayPeriod::class,
            'end_period' => DayPeriod::class,
            'status' => LeaveStatus::class,
            'approved_at' => 'datetime',
            'total_days' => 'decimal:1',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** @param Builder<LeaveRequest> $query */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LeaveStatus::Pending);
    }

    /** @param Builder<LeaveRequest> $query */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', LeaveStatus::Approved);
    }
}
