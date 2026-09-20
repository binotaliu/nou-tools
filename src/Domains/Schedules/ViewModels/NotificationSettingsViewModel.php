<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

/**
 * The remembered schedule's two push opt-ins for the 設定 page. Both are the
 * server-side intent only: whether this browser also holds a push
 * subscription is something the page finds out for itself.
 */
final class NotificationSettingsViewModel extends Data
{
    public function __construct(
        public string $scheduleToken,
        public bool $classReminders,
        public bool $hasStudyRoomProfile,
        public bool $timerEnd,
    ) {}
}
