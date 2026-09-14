<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ClassSchedule;
use App\Models\StudentSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

final class ClassStartingSoon extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ClassSchedule $classSchedule,
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(StudentSchedule $notifiable): WebPushMessage
    {
        $courseClass = $this->classSchedule->courseClass;
        $course = $courseClass->course;

        return (new WebPushMessage)
            ->title("{$course->name} 10 分鐘後開始")
            ->icon('/icons/icon-192.png')
            ->body('視訊面授即將開始，點擊檢視課表與連結。')
            ->data(['url' => route('schedules.show', $notifiable)])
            ->options(['TTL' => 600]);
    }
}
