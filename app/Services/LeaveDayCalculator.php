<?php

namespace App\Services;

use App\Enums\DayPeriod;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class LeaveDayCalculator
{
    /**
     * Calculate the number of leave days between two dates (inclusive),
     * excluding weekends and holidays, with optional half-day adjustments
     * on the boundary days.
     *
     * @param  CarbonInterface  $start  First day of leave (inclusive).
     * @param  CarbonInterface  $end  Last day of leave (inclusive).
     * @param  DayPeriod  $startPeriod  Period for the first day.
     * @param  DayPeriod  $endPeriod  Period for the last day.
     */
    public function calculate(
        CarbonInterface $start,
        CarbonInterface $end,
        DayPeriod $startPeriod = DayPeriod::Full,
        DayPeriod $endPeriod = DayPeriod::Full,
    ): float {
        if ($end->lt($start)) {
            return 0.0;
        }

        [$exactDates, $recurringMonthDays] = $this->fetchHolidayLookups($start, $end);

        $workingDays = 0;
        $startIsWorkingDay = false;
        $endIsWorkingDay = false;

        $startDate = $start->toImmutable()->startOfDay();
        $endDate = $end->toImmutable()->startOfDay();

        /** @var Carbon $day */
        foreach (CarbonPeriod::create($startDate, $endDate) as $day) {
            if ($this->isNonWorkingDay($day, $exactDates, $recurringMonthDays)) {
                continue;
            }

            $workingDays++;

            if ($day->isSameDay($startDate)) {
                $startIsWorkingDay = true;
            }

            if ($day->isSameDay($endDate)) {
                $endIsWorkingDay = true;
            }
        }

        if ($workingDays === 0) {
            return 0.0;
        }

        $total = (float) $workingDays;

        if ($startIsWorkingDay && $startPeriod === DayPeriod::Afternoon) {
            $total -= 0.5;
        }

        if ($endIsWorkingDay && $endPeriod === DayPeriod::Morning) {
            $total -= 0.5;
        }

        return max(0.0, $total);
    }

    /**
     * Build two holiday lookup structures in a single query:
     * - $exactDates: set of 'Y-m-d' strings for non-recurring holidays in [start, end]
     * - $recurringMonthDays: set of 'm-d' strings for recurring holidays (all years)
     *
     * @return array{0: array<string, true>, 1: array<string, true>}
     */
    private function fetchHolidayLookups(CarbonInterface $start, CarbonInterface $end): array
    {
        $holidays = Holiday::query()
            ->where(function ($query) use ($start, $end): void {
                $query->where('is_recurring', false)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
            })
            ->orWhere('is_recurring', true)
            ->get(['date', 'is_recurring']);

        /** @var array<string, true> $exactDates */
        $exactDates = [];

        /** @var array<string, true> $recurringMonthDays */
        $recurringMonthDays = [];

        foreach ($holidays as $holiday) {
            if ($holiday->is_recurring) {
                $recurringMonthDays[$holiday->date->format('m-d')] = true;
            } else {
                $exactDates[$holiday->date->toDateString()] = true;
            }
        }

        return [$exactDates, $recurringMonthDays];
    }

    /**
     * Determine whether a given day is a non-working day (weekend or holiday).
     *
     * @param  array<string, true>  $exactDates
     * @param  array<string, true>  $recurringMonthDays
     */
    private function isNonWorkingDay(
        CarbonInterface $day,
        array $exactDates,
        array $recurringMonthDays,
    ): bool {
        if ($day->isWeekend()) {
            return true;
        }

        if (isset($exactDates[$day->toDateString()])) {
            return true;
        }

        if (isset($recurringMonthDays[$day->format('m-d')])) {
            return true;
        }

        return false;
    }
}
