<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Filament\Notifications\Notification;

class LeaveRequestNotifier
{
    public function approved(LeaveRequest $leaveRequest): void
    {
        $user = $leaveRequest->user;

        if (! $user) {
            return;
        }

        $leaveRequest->loadMissing('leaveType');

        $leaveTypeName = $leaveRequest->leaveType?->name ?? 'Leave';
        $startDate = $leaveRequest->start_date->format('M j, Y');
        $endDate = $leaveRequest->end_date->format('M j, Y');
        $totalDays = $leaveRequest->total_days;

        $body = "Your {$leaveTypeName} for {$startDate}–{$endDate} ({$totalDays} day(s)) was approved.";

        $notification = Notification::make()
            ->title('Leave request approved')
            ->success()
            ->body($body);

        // Deliver synchronously: Filament's DatabaseNotification is ShouldQueue, and
        // this app runs on the database queue without a guaranteed worker (e.g. plain
        // Herd). notifyNow() bypasses the queue so the employee is notified immediately.
        $user->notifyNow($notification->toDatabase());
    }

    public function rejected(LeaveRequest $leaveRequest): void
    {
        $user = $leaveRequest->user;

        if (! $user) {
            return;
        }

        $leaveRequest->loadMissing('leaveType');

        $leaveTypeName = $leaveRequest->leaveType?->name ?? 'Leave';
        $startDate = $leaveRequest->start_date->format('M j, Y');
        $endDate = $leaveRequest->end_date->format('M j, Y');
        $reason = $leaveRequest->rejection_reason ?? 'No reason provided';

        $body = "Your {$leaveTypeName} for {$startDate}–{$endDate} was rejected. Reason: {$reason}";

        $notification = Notification::make()
            ->title('Leave request rejected')
            ->danger()
            ->body($body);

        // Deliver synchronously (see approved() — avoids the queue-worker dependency).
        $user->notifyNow($notification->toDatabase());
    }
}
