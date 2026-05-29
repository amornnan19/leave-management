<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Enums\LeaveStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Employee'),

                TextEntry::make('leaveType.name')
                    ->label('Leave Type'),

                TextEntry::make('start_date')
                    ->date(),

                TextEntry::make('end_date')
                    ->date(),

                TextEntry::make('start_period')
                    ->label('Start Period'),

                TextEntry::make('end_period')
                    ->label('End Period'),

                TextEntry::make('total_days')
                    ->label('Total Days'),

                TextEntry::make('reason'),

                TextEntry::make('status')
                    ->badge()
                    ->color(fn (LeaveStatus $state): string => match ($state) {
                        LeaveStatus::Pending => 'warning',
                        LeaveStatus::Approved => 'success',
                        LeaveStatus::Rejected => 'danger',
                        LeaveStatus::Cancelled => 'gray',
                    }),

                TextEntry::make('approver.name')
                    ->label('Approved / Rejected By'),

                TextEntry::make('approved_at')
                    ->label('Actioned At')
                    ->dateTime(),

                TextEntry::make('rejection_reason')
                    ->label('Rejection Reason'),

                TextEntry::make('attachment_path')
                    ->label('Attachment'),
            ]);
    }
}
