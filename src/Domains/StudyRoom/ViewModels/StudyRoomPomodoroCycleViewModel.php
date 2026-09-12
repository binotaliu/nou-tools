<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;
use Spatie\LaravelData\Data;

final class StudyRoomPomodoroCycleViewModel extends Data
{
    public function __construct(
        public int $focusMinutes,
        public int $shortBreakMinutes,
        public int $longBreakMinutes,
        public int $roundsPerCycle,
    ) {}

    public static function fromCycle(PomodoroCycle $cycle): self
    {
        return new self(
            focusMinutes: $cycle->focusMinutes,
            shortBreakMinutes: $cycle->shortBreakMinutes,
            longBreakMinutes: $cycle->longBreakMinutes,
            roundsPerCycle: $cycle->roundsPerCycle,
        );
    }
}
