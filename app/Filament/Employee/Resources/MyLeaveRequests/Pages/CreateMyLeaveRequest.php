<?php

namespace App\Filament\Employee\Resources\MyLeaveRequests\Pages;

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use App\Filament\Employee\Resources\MyLeaveRequests\MyLeaveRequestResource;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveRequestValidator;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMyLeaveRequest extends CreateRecord
{
    protected static string $resource = MyLeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Security: always force user_id to the authenticated employee.
        // Never trust a submitted or tampered field.
        $data['user_id'] = auth()->id();

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

    private function normalizeDayPeriod(mixed $value): DayPeriod
    {
        if ($value instanceof DayPeriod) {
            return $value;
        }

        return $value !== null ? DayPeriod::from($value) : DayPeriod::Full;
    }
}
