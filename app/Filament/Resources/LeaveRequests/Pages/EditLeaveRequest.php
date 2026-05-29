<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Enums\DayPeriod;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveRequestValidator;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

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

        // Fall back to the persisted record's values if the form omits these fields.
        $data['user_id'] ??= $this->record->user_id;
        $data['leave_type_id'] ??= $this->record->leave_type_id;

        /** @var LeaveRequestValidator $validator */
        $validator = app(LeaveRequestValidator::class);
        $violations = $validator->validate($data, $this->record->id);

        if ($violations !== []) {
            // Prefix each key with "data." so Filament maps errors to the correct form field inline
            $prefixed = [];
            foreach ($violations as $field => $message) {
                $prefixed["data.{$field}"] = $message;
            }

            throw ValidationException::withMessages($prefixed);
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
