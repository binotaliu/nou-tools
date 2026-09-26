<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Schedules\ViewModels\NotificationSettingsViewModel;

final readonly class ShowNotificationSettings
{
    public function __construct(private ShowScheduleBackup $showScheduleBackup) {}

    public function __invoke(StudentScheduleCookie $viewer): NotificationSettingsViewModel
    {
        $schedule = StudentSchedule::query()->with('studyRoomProfile')->findOrFail($viewer->id);

        return new NotificationSettingsViewModel(
            scheduleToken: $viewer->token,
            classReminders: $schedule->notify_on_class_start,
            hasStudyRoomProfile: $schedule->studyRoomProfile !== null,
            timerEnd: $schedule->studyRoomProfile?->notify_on_timer_end ?? false,
            backup: ($this->showScheduleBackup)($schedule),
        );
    }
}
