<?php

namespace App\Filament\Employee\Resources\MyLeaveRequests\Tables;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MyLeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Days')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (LeaveStatus $state): string => match ($state) {
                        LeaveStatus::Pending => 'warning',
                        LeaveStatus::Approved => 'success',
                        LeaveStatus::Rejected => 'danger',
                        LeaveStatus::Cancelled => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LeaveStatus::class),

                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name'),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record): bool => $record->status === LeaveStatus::Pending
                        && auth()->user()->can('cancel', $record))
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()->can('cancel', $record))
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record): void {
                        // Re-guard: reload from DB to prevent TOCTOU race
                        $record->refresh();

                        if ($record->status !== LeaveStatus::Pending) {
                            Notification::make()->danger()->title('Request can no longer be cancelled')->send();

                            return;
                        }

                        $record->update(['status' => LeaveStatus::Cancelled]);

                        Notification::make()
                            ->title('Leave request cancelled')
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
            ]);
    }
}
