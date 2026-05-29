<?php

namespace App\Models;

use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'color',
        'is_paid',
        'requires_attachment',
        'default_days_per_year',
        'max_consecutive_days',
        'min_notice_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
            'default_days_per_year' => 'decimal:1',
            'min_notice_days' => 'integer',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /** @param Builder<LeaveType> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
