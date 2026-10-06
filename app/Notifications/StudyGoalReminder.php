<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StudentSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Nudges a student to study at the time they chose for today. The body names
 * what is left of today's goal, else of the week's, so it is a number they
 * can act on rather than a generic prompt.
 */
final class StudyGoalReminder extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ?int $remainingTodayMinutes,
        private readonly ?int $remainingWeekMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(StudentSchedule $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('該來自習室了')
            ->icon('/icons/icon-192.png')
            ->body($this->body())
            ->data(['url' => route('study-room.show')])
            ->tag('study-goal')
            ->options(['TTL' => 3600]);
    }

    private function body(): string
    {
        if ($this->remainingTodayMinutes !== null) {
            return "今天的目標還差 {$this->remainingTodayMinutes} 分鐘，現在就去自習室吧。";
        }

        if ($this->remainingWeekMinutes !== null) {
            return "本週的目標還差 {$this->remainingWeekMinutes} 分鐘，今天來累積一點吧。";
        }

        return '到你設定的自習時間了，來自習室專心一下吧。';
    }
}
