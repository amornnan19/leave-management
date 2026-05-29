<?php

namespace App\Filament\Employee\Resources\MyLeaveRequests\Schemas;

use App\Enums\DayPeriod;
use App\Models\LeaveType;
use App\Services\LeaveDayCalculator;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MyLeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // user_id is intentionally omitted — the portal forces it to auth()->id()
                // in CreateMyLeaveRequest::mutateFormDataBeforeCreate.

                Select::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship(name: 'leaveType', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),

                DatePicker::make('start_date')
                    ->required()
                    ->live(),

                DatePicker::make('end_date')
                    ->required()
                    ->live()
                    ->afterOrEqual('start_date'),

                Select::make('start_period')
                    ->label('Start Period')
                    ->options(DayPeriod::class)
                    ->default(DayPeriod::Full)
                    ->required()
                    ->live(),

                Select::make('end_period')
                    ->label('End Period')
                    ->options(DayPeriod::class)
                    ->default(DayPeriod::Full)
                    ->required()
                    ->live(),

                Placeholder::make('estimated_days')
                    ->label('Estimated Days')
                    ->content(function (Get $get): string {
                        $startDate = $get('start_date');
                        $endDate = $get('end_date');
                        $startPeriod = $get('start_period');
                        $endPeriod = $get('end_period');

                        if (! $startDate || ! $endDate) {
                            return '—';
                        }

                        try {
                            /** @var LeaveDayCalculator $calculator */
                            $calculator = app(LeaveDayCalculator::class);

                            $days = $calculator->calculate(
                                Carbon::parse($startDate),
                                Carbon::parse($endDate),
                                $startPeriod instanceof DayPeriod ? $startPeriod : DayPeriod::from($startPeriod ?? DayPeriod::Full->value),
                                $endPeriod instanceof DayPeriod ? $endPeriod : DayPeriod::from($endPeriod ?? DayPeriod::Full->value),
                            );

                            return "{$days} day(s)";
                        } catch (\Throwable) {
                            return '—';
                        }
                    }),

                Textarea::make('reason')
                    ->rows(3)
                    ->nullable(),

                FileUpload::make('attachment_path')
                    ->label('Attachment')
                    ->disk('public')
                    ->directory('leave-attachments')
                    ->nullable()
                    ->required(function (Get $get): bool {
                        $leaveTypeId = $get('leave_type_id');
                        if (! $leaveTypeId) {
                            return false;
                        }

                        $leaveType = LeaveType::find($leaveTypeId);

                        return (bool) ($leaveType?->requires_attachment ?? false);
                    }),
            ]);
    }
}
