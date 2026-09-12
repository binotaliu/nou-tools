<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ValueObjects;

use App\Models\StudyRoomProfile;

/**
 * One student's pomodoro cycle: `roundsPerCycle` focus rounds of
 * `focusMinutes`, each followed by a short break, except the last of the
 * cycle which is followed by a long break. Rounds are numbered from 1 and
 * keep counting past the first cycle, so round 5 is the first round of the
 * second cycle.
 */
final readonly class PomodoroCycle
{
    public function __construct(
        public int $focusMinutes,
        public int $shortBreakMinutes,
        public int $longBreakMinutes,
        public int $roundsPerCycle,
    ) {}

    public static function default(): self
    {
        return new self(
            focusMinutes: (int) config('study-room.timer.pomodoro.focus_minutes'),
            shortBreakMinutes: (int) config('study-room.timer.pomodoro.short_break_minutes'),
            longBreakMinutes: (int) config('study-room.timer.pomodoro.long_break_minutes'),
            roundsPerCycle: (int) config('study-room.timer.pomodoro.rounds_per_cycle'),
        );
    }

    /**
     * The student's saved cycle, falling back to the config default for
     * anything they haven't set (or when they have no profile at all).
     */
    public static function forProfile(?StudyRoomProfile $profile): self
    {
        $default = self::default();

        return new self(
            focusMinutes: $profile?->pomodoro_focus_minutes ?? $default->focusMinutes,
            shortBreakMinutes: $profile?->pomodoro_short_break_minutes ?? $default->shortBreakMinutes,
            longBreakMinutes: $profile?->pomodoro_long_break_minutes ?? $default->longBreakMinutes,
            roundsPerCycle: $profile?->pomodoro_rounds_per_cycle ?? $default->roundsPerCycle,
        );
    }

    public function isLastRoundOfCycle(int $round): bool
    {
        return $round % $this->roundsPerCycle === 0;
    }

    public function breakMinutesAfterRound(int $round): int
    {
        return $this->isLastRoundOfCycle($round) ? $this->longBreakMinutes : $this->shortBreakMinutes;
    }
}
