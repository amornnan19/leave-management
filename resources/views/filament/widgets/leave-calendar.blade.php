<x-filament-widgets::widget>
    <x-filament::section>

        <style>
            /* ── Scoped to .leave-cal so nothing leaks into Filament ────────────── */

            .leave-cal {
                font-family: inherit;
            }

            /* Header */
            .leave-cal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
            }

            .leave-cal-title {
                font-size: 1.125rem;
                font-weight: 600;
                letter-spacing: -0.015em;
                color: #C5CBD4; /* mist */
                margin: 0;
            }

            .leave-cal-nav {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .leave-cal-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #262B35; /* ash */
                background: transparent;
                color: #7B828F; /* fog */
                border-radius: 6px;
                cursor: pointer;
                transition: border-color 0.15s, color 0.15s;
                line-height: 1;
            }

            .leave-cal-btn:hover {
                border-color: #34F5C5; /* spectre */
                color: #34F5C5;
            }

            .leave-cal-btn-today {
                padding: 5px 12px;
                font-size: 0.8125rem;
                font-weight: 500;
            }

            .leave-cal-btn-arrow {
                padding: 6px;
            }

            .leave-cal-btn-arrow svg {
                width: 14px;
                height: 14px;
                display: block;
            }

            /* Weekday label row */
            .leave-cal-dow {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                margin-bottom: 2px;
            }

            .leave-cal-dow-cell {
                padding: 6px 4px;
                text-align: center;
                font-size: 0.6875rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #7B828F; /* fog */
            }

            .leave-cal-dow-cell--weekend {
                color: #4B5059; /* dimmer fog for weekends */
            }

            /* Main grid */
            .leave-cal-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 1px;
                background-color: #262B35; /* ash — the 1px gap shows through as border lines */
                border: 1px solid #262B35;
                border-radius: 8px;
                overflow: hidden;
            }

            /* Day cell */
            .leave-cal-cell {
                min-height: 108px;
                padding: 6px;
                display: flex;
                flex-direction: column;
                gap: 3px;
                background-color: #14171E; /* graphite */
            }

            .leave-cal-cell--weekend {
                background-color: #0E1117; /* slightly darker */
            }

            .leave-cal-cell--out {
                opacity: 0.35;
            }

            /* Day number */
            .leave-cal-day-num {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 2px;
            }

            .leave-cal-day-num span {
                font-size: 0.6875rem;
                font-weight: 500;
                color: #7B828F; /* fog — default / out-of-month */
                line-height: 1;
            }

            .leave-cal-day-num span.in-month {
                color: #C5CBD4; /* mist */
            }

            /* Today badge — brand gradient circle (guaranteed visible on dark) */
            .leave-cal-day-num span.today-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                background: linear-gradient(135deg, #34F5C5 0%, #6D5DFC 100%); /* brand */
                color: #06070A; /* void — dark text on bright gradient */
                font-weight: 700;
                font-size: 0.6875rem;
                box-shadow: 0 0 10px rgba(52, 245, 197, 0.45);
            }

            /* Holiday pill */
            .leave-cal-holiday {
                display: inline-block;
                padding: 1px 5px;
                border-radius: 3px;
                font-size: 0.625rem;
                font-weight: 600;
                line-height: 1.5;
                background-color: rgba(251, 146, 60, 0.18); /* amber/20 */
                color: #FBB03B; /* amber */
                border: 1px solid rgba(251, 146, 60, 0.28);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 100%;
            }

            /* Leave pill */
            .leave-cal-leave {
                display: flex;
                align-items: center;
                gap: 4px;
                max-width: 100%;
                overflow: hidden;
            }

            .leave-cal-leave-dot {
                flex-shrink: 0;
                width: 7px;
                height: 7px;
                border-radius: 50%;
            }

            .leave-cal-leave-name {
                font-size: 0.625rem;
                line-height: 1.4;
                color: #C5CBD4; /* mist */
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                flex: 1 1 0;
                min-width: 0;
            }

            /* +N more */
            .leave-cal-more {
                font-size: 0.625rem;
                color: #7B828F; /* fog */
                line-height: 1.4;
            }

            /* Legend */
            .leave-cal-legend {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 4px 16px;
                margin-top: 14px;
                font-size: 0.75rem;
                color: #7B828F;
            }

            .leave-cal-legend-item {
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .leave-cal-legend-today {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: linear-gradient(135deg, #34F5C5 0%, #6D5DFC 100%);
            }

            .leave-cal-legend-holiday {
                display: inline-block;
                width: 16px;
                height: 8px;
                border-radius: 2px;
                background-color: rgba(251, 146, 60, 0.35);
                border: 1px solid rgba(251, 146, 60, 0.4);
            }

            .leave-cal-legend-leave {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background-color: #7B828F;
            }
        </style>

        <div class="leave-cal">

            {{-- ── Header: month title + navigation ──────────────────────────── --}}
            <div class="leave-cal-header">
                <h2 class="leave-cal-title">{{ $this->getHeading() }}</h2>

                <div class="leave-cal-nav">
                    <button
                        type="button"
                        wire:click="previousMonth"
                        class="leave-cal-btn leave-cal-btn-arrow"
                        aria-label="Previous month"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>

                    <button
                        type="button"
                        wire:click="goToToday"
                        class="leave-cal-btn leave-cal-btn-today"
                    >
                        Today
                    </button>

                    <button
                        type="button"
                        wire:click="nextMonth"
                        class="leave-cal-btn leave-cal-btn-arrow"
                        aria-label="Next month"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- ── Weekday header row ──────────────────────────────────────────── --}}
            <div class="leave-cal-dow">
                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                    <div class="leave-cal-dow-cell {{ in_array($dayName, ['Sat', 'Sun']) ? 'leave-cal-dow-cell--weekend' : '' }}">
                        {{ $dayName }}
                    </div>
                @endforeach
            </div>

            {{-- ── Calendar grid ───────────────────────────────────────────────── --}}
            <div class="leave-cal-grid">
                @foreach($this->getCalendarWeeks() as $week)
                    @foreach($week as $cell)
                        @php
                            $leaves = $cell['leaves'];
                            $visibleLeaves = array_slice($leaves, 0, 3);
                            $extraCount = count($leaves) - count($visibleLeaves);
                        @endphp

                        <div class="leave-cal-cell{{ $cell['isWeekend'] ? ' leave-cal-cell--weekend' : '' }}{{ ! $cell['inMonth'] ? ' leave-cal-cell--out' : '' }}">

                            {{-- Day number --}}
                            <div class="leave-cal-day-num">
                                @if($cell['isToday'])
                                    <span class="today-badge">{{ $cell['date']->day }}</span>
                                @else
                                    <span class="{{ $cell['inMonth'] ? 'in-month' : '' }}">{{ $cell['date']->day }}</span>
                                @endif
                            </div>

                            {{-- Holidays --}}
                            @foreach($cell['holidays'] as $holiday)
                                <span class="leave-cal-holiday" title="{{ $holiday }}">{{ $holiday }}</span>
                            @endforeach

                            {{-- People on leave --}}
                            @foreach($visibleLeaves as $leave)
                                <div class="leave-cal-leave" title="{{ $leave['name'] }} — {{ $leave['type'] }}{{ $leave['half'] ? ' ('.$leave['half'].')' : '' }}">
                                    <span class="leave-cal-leave-dot" style="background-color: {{ $leave['color'] }}"></span>
                                    <span class="leave-cal-leave-name">{{ $leave['name'] }}</span>
                                    @if($leave['half'])
                                        <span style="font-size:9px;font-weight:700;letter-spacing:0.03em;background:rgba(52,211,153,0.15);color:#6EE7B7;border-radius:3px;padding:1px 4px;flex-shrink:0;white-space:nowrap;">½{{ $leave['half'] }}</span>
                                    @endif
                                </div>
                            @endforeach

                            @if($extraCount > 0)
                                <span class="leave-cal-more">+{{ $extraCount }} more</span>
                            @endif

                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- ── Legend ─────────────────────────────────────────────────────── --}}
            <div class="leave-cal-legend">
                <div class="leave-cal-legend-item">
                    <span class="leave-cal-legend-today"></span>
                    Today
                </div>
                <div class="leave-cal-legend-item">
                    <span class="leave-cal-legend-holiday"></span>
                    Holiday
                </div>
                <div class="leave-cal-legend-item">
                    <span style="font-size:9px;font-weight:700;background:rgba(52,211,153,0.15);color:#6EE7B7;border-radius:3px;padding:1px 4px;">½AM</span>
                    Half day
                </div>

                {{-- Leave-type colour key — dots match the per-leave dots in the grid --}}
                <span style="opacity:0.45;">On leave:</span>
                @foreach($this->getLeaveTypeLegend() as $type)
                    <div class="leave-cal-legend-item">
                        <span class="leave-cal-leave-dot" style="background-color: {{ $type['color'] }}"></span>
                        {{ $type['name'] }}
                    </div>
                @endforeach
            </div>

        </div>

    </x-filament::section>
</x-filament-widgets::widget>
