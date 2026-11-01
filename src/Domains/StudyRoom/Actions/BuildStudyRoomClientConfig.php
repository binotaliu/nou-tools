<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

/**
 * The study room's client-side config (timer bounds, heartbeat, layout). The
 * café reads the same timer limits, so it builds this too.
 */
final readonly class BuildStudyRoomClientConfig
{
    /**
     * @return array<string, int|float|array<int, int>>
     */
    public function __invoke(): array
    {
        $bounds = config('study-room.timer.pomodoro.bounds');

        return [
            'heartbeatIntervalSeconds' => (int) config('study-room.heartbeat.interval_seconds'),
            'heartbeatIdleReleaseSeconds' => (int) config('study-room.heartbeat.idle_release_seconds'),
            'realtimeConnectTimeoutSeconds' => (int) config('study-room.realtime.connect_timeout_seconds'),
            'timerPomodoroFocusMinutes' => (int) config('study-room.timer.pomodoro.focus_minutes'),
            'timerPomodoroShortBreakMinutes' => (int) config('study-room.timer.pomodoro.short_break_minutes'),
            'timerPomodoroLongBreakMinutes' => (int) config('study-room.timer.pomodoro.long_break_minutes'),
            'timerPomodoroRoundsPerCycle' => (int) config('study-room.timer.pomodoro.rounds_per_cycle'),
            'timerPomodoroFocusBounds' => array_map(intval(...), $bounds['focus_minutes']),
            'timerPomodoroBreakBounds' => array_map(intval(...), $bounds['break_minutes']),
            'timerPomodoroRoundsBounds' => array_map(intval(...), $bounds['rounds_per_cycle']),
            'timerCustomMinMinutes' => (int) config('study-room.timer.custom.min_minutes'),
            'timerCustomMaxMinutes' => (int) config('study-room.timer.custom.max_minutes'),
            'timerMaxSessionSeconds' => (int) config('study-room.timer.max_session_seconds'),
            'maxFloors' => (int) config('study-room.floors.max'),
            // Seat layout is fixed per deploy (see BuildSeatDefinitions), so
            // it's safe to expose here for the initial skeleton render —
            // unlike occupancy/openFloors, which are genuinely live and stay
            // out of Inertia props (see StudyRoomController::show).
            'soloSeatsPerFloor' => (int) config('study-room.layout.solo_seats_per_floor'),
            'tablesPerFloor' => (int) config('study-room.layout.tables_per_floor'),
            'seatsPerTable' => (int) config('study-room.layout.seats_per_table'),
            'latitude' => (float) config('study-room.location.latitude'),
            'longitude' => (float) config('study-room.location.longitude'),
            'nicknameMinLength' => (int) config('study-room.nickname.min_length'),
            'nicknameMaxLength' => (int) config('study-room.nickname.max_length'),
            'nicknameCooldownDays' => (int) config('study-room.nickname.cooldown_days'),
        ];
    }
}
