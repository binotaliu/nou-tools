<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use NouTools\Domains\StudyRoom\ViewModels\StudyRoomPomodoroCycleViewModel;
use Spatie\LaravelData\Data;

final class CafePageViewModel extends Data
{
    /**
     * The timer inputs mirror the study room's page: the café runs the same
     * focus timer on its seats.
     *
     * @param  array<int, mixed>  $verbs
     * @param  array<int, mixed>  $subjects
     * @param  array<string, mixed>  $timerConfig
     */
    public function __construct(
        public bool $hasSchedule,
        public bool $hasNickname,
        public ?string $nickname,
        public ?string $emoji,
        public int $heartbeatIntervalSeconds,
        public int $connectTimeoutSeconds,
        public int $notePrice,
        public int $noteMaxLength,
        public int $noteLifetimeDays,
        public int $orderGraceSeconds,
        public StudyRoomPomodoroCycleViewModel $pomodoroCycle,
        public bool $playSoundOnTimerEnd,
        public array $verbs,
        public array $subjects,
        public array $timerConfig,
    ) {}
}
