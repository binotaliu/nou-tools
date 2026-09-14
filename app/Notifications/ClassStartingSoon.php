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
            ->title("{$course->name} 即將開始上課")
            ->icon('/icons/icon-192.png')
            ->body("「{$course->name}」將於 10 分鐘後開始上課。點擊通知以開啟 NOU 小幫手課表。")
            ->data(['url' => route('schedules.show', $notifiable)])
            ->options(['TTL' => 600]);
    }
}
