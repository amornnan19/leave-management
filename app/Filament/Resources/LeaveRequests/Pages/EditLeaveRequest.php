<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Enums\DayPeriod;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Services\LeaveDayCalculator;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['start_date'], $data['end_date'])) {
            /** @var LeaveDayCalculator $calculator */
            $calculator = app(LeaveDayCalculator::class);

            $data['total_days'] = $calculator->calculate(
                Carbon::parse($data['start_date']),
                Carbon::parse($data['end_date']),
                $this->normalizeDayPeriod($data['start_period'] ?? null),
                $this->normalizeDayPeriod($data['end_period'] ?? null),
            );
        }

        return $data;
    }

    private function normalizeDayPeriod(mixed $value): DayPeriod
    {
        if ($value instanceof DayPeriod) {
            return $value;
        }

        return $value !== null ? DayPeriod::from($value) : DayPeriod::Full;
    }
}
