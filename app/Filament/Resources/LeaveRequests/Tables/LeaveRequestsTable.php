<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestNotifier;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

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
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record): bool => $record->status === LeaveStatus::Pending
                        && auth()->user()->can('approve', $record))
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()->can('approve', $record))
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record): void {
                        if ($record->status !== LeaveStatus::Pending) {
                            Notification::make()->danger()->title('Request already processed')->send();

                            return;
                        }

                        $record->update([
                            'status' => LeaveStatus::Approved,
                            'approver_id' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        app(LeaveRequestNotifier::class)->approved($record);

                        Notification::make()
                            ->title('Leave request approved')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record): bool => $record->status === LeaveStatus::Pending
                        && auth()->user()->can('approve', $record))
                    ->authorize(fn (LeaveRequest $record): bool => auth()->user()->can('approve', $record))
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (LeaveRequest $record, array $data): void {
                        if ($record->status !== LeaveStatus::Pending) {
                            Notification::make()->danger()->title('Request already processed')->send();

                            return;
                        }

                        $record->update([
                            'status' => LeaveStatus::Rejected,
                            'approver_id' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        app(LeaveRequestNotifier::class)->rejected($record);

                        Notification::make()
                            ->title('Leave request rejected')
                            ->warning()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
