<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveRequestNotifier;
use App\Services\LeaveRequestValidator;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = LeaveStatus::Pending->value;

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

        /** @var LeaveRequestValidator $validator */
        $validator = app(LeaveRequestValidator::class);
        $violations = $validator->validate($data);

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

    protected function afterCreate(): void
    {
        app(LeaveRequestNotifier::class)->submitted($this->record);
    }

    private function normalizeDayPeriod(mixed $value): DayPeriod
    {
        if ($value instanceof DayPeriod) {
            return $value;
        }

        return $value !== null ? DayPeriod::from($value) : DayPeriod::Full;
    }
}
