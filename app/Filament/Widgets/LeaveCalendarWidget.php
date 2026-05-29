<?php

namespace App\Filament\Widgets;

use App\Enums\DayPeriod;
use App\Enums\LeaveStatus;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class LeaveCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.leave-calendar';

    protected int|string|array $columnSpan = 'full';

    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function goToToday(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    /**
     * Return a Monday-first 6-row calendar grid for the current $year/$month.
     *
     * Each cell:
     * [
     *   'date'     => Carbon,
     *   'inMonth'  => bool,
     *   'isToday'  => bool,
     *   'isWeekend'=> bool,
     *   'holidays' => string[],             // holiday names
     *   'leaves'   => [['name','color','type','half'], ...],
     * ]
     *
     * Two base queries (holidays + leaves) plus Eloquent's eager-load queries for
     * user/leaveType — a small constant total, independent of leave/day count (no N+1).
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getCalendarWeeks(): array
    {
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);
        $lastOfMonth = $firstOfMonth->copy()->endOfMonth();

        // Monday-first grid: pad to previous Monday, end on next Sunday
        $gridStart = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Ensure at least 6 rows (42 cells)
        if ($gridStart->diffInDays($gridEnd) < 41) {
            $gridEnd = $gridStart->copy()->addDays(41);
        }

        // ── Query 1: all holidays ──────────────────────────────────────────────
        $allHolidays = Holiday::query()->get(['name', 'date', 'is_recurring']);

        /** @var array<string, true> $exactHolidayDates  key = 'Y-m-d' */
        $exactHolidayDates = [];

        /** @var array<string, string[]> $recurringHolidays  key = 'm-d', value = names[] */
        $recurringHolidays = [];

        /** @var array<string, string[]> $exactHolidayNames  key = 'Y-m-d', value = names[] */
        $exactHolidayNames = [];

        foreach ($allHolidays as $holiday) {
            if ($holiday->is_recurring) {
                $key = $holiday->date->format('m-d');
                $recurringHolidays[$key][] = $holiday->name;
            } else {
                $key = $holiday->date->toDateString();
                $exactHolidayNames[$key][] = $holiday->name;
            }
        }

        // ── Query 2: approved leaves intersecting the grid window ─────────────
        $leaveRequests = LeaveRequest::query()
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $gridEnd->toDateString())
            ->whereDate('end_date', '>=', $gridStart->toDateString())
            ->with(['user:id,name', 'leaveType:id,name,color'])
            ->get(['id', 'user_id', 'leave_type_id', 'start_date', 'end_date', 'start_period', 'end_period', 'total_days']);

        // Pre-index leaves by date string for O(1) lookup
        /** @var array<string, array<int, array{name: string, color: string, type: string, half: string|null}>> $leavesByDate */
        $leavesByDate = [];

        foreach ($leaveRequests as $request) {
            $cursor = $request->start_date->copy()->startOfDay();
            $leaveEnd = $request->end_date->copy()->startOfDay();
            $isSingleDay = $request->start_date->isSameDay($request->end_date);

            while ($cursor->lte($leaveEnd)) {
                $dayKey = $cursor->toDateString();

                // Determine half-day label for this specific grid day
                if ($isSingleDay && (float) $request->total_days <= 0.5) {
                    $half = $request->start_period === DayPeriod::Afternoon ? 'PM' : 'AM';
                } elseif ($cursor->isSameDay($request->start_date) && $request->start_period === DayPeriod::Afternoon) {
                    $half = 'PM';
                } elseif ($cursor->isSameDay($request->end_date) && $request->end_period === DayPeriod::Morning) {
                    $half = 'AM';
                } else {
                    $half = null;
                }

                $leavesByDate[$dayKey][] = [
                    'name' => $request->user->name,
                    'color' => $request->leaveType->color,
                    'type' => $request->leaveType->name,
                    'half' => $half,
                ];
                $cursor->addDay();
            }
        }

        // ── Build 6-row grid ──────────────────────────────────────────────────
        $today = now()->startOfDay();
        $weeks = [];
        $current = $gridStart->copy();

        for ($week = 0; $week < 6; $week++) {
            $row = [];

            for ($day = 0; $day < 7; $day++) {
                $dateStr = $current->toDateString();
                $monthDay = $current->format('m-d');

                // Collect holiday names for this day (O(1) — keys are already 'Y-m-d' / 'm-d')
                $holidayNames = array_merge(
                    $exactHolidayNames[$dateStr] ?? [],
                    $recurringHolidays[$monthDay] ?? [],
                );

                $row[] = [
                    'date' => $current->copy(),
                    'inMonth' => $current->month === $this->month,
                    'isToday' => $current->isSameDay($today),
                    'isWeekend' => $current->isWeekend(),
                    'holidays' => $holidayNames,
                    'leaves' => $leavesByDate[$dateStr] ?? [],
                ];

                $current->addDay();
            }

            $weeks[] = $row;
        }

        return $weeks;
    }

    /**
     * Human-readable heading for the viewed month.
     */
    public function getHeading(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    /**
     * Active leave types with their display color, for the calendar legend.
     * The dot colors here match the per-leave dots rendered in the grid.
     *
     * @return array<int, array{name: string, color: string}>
     */
    public function getLeaveTypeLegend(): array
    {
        return LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'color'])
            ->map(fn (LeaveType $type): array => [
                'name' => $type->name,
                'color' => $type->color,
            ])
            ->all();
    }
}
