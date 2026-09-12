<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use DateTimeInterface;
use Spatie\LaravelData\Data;

/**
 * The viewer's own 自習室 profile (nickname + emoji), including whether the
 * 7-day nickname cooldown currently blocks a change. Never rendered for
 * anyone but the viewer themselves — other students only ever see the
 * public projection in `StudyRoomSeatViewModel`.
 */
final class StudyRoomProfileViewModel extends Data
{
    public function __construct(
        public ?string $nickname,
        public ?string $emoji,
        public ?DateTimeInterface $nicknameChangedAt,
        public ?DateTimeInterface $canChangeNicknameAt,
        public bool $canChangeNickname,
        public StudyRoomPomodoroCycleViewModel $pomodoroCycle,
        public bool $playSoundOnTimerEnd,
    ) {}
}
