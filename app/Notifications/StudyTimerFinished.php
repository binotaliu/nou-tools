<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\StudyTimerPhase;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;

/**
 * Tells a student their 自習室 timer ran out. The page only chimes while
 * it is visible, and a phone suspends or discards a backgrounded tab
 * outright, so this is the only thing that reaches them once they look
 * away.
 */
final class StudyTimerFinished extends Notification
{
    use Queueable;

    public function __construct(
        private readonly StudyRoomSeat $seat,
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(StudentSchedule $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title())
            ->icon('/icons/icon-192.png')
            ->body($this->body())
            ->data(['url' => route('study-room.show')])
            // One notification per seat: a replacement should overwrite the
            // previous round rather than stack up in the tray.
            ->tag("study-timer:{$this->seat->code}")
            ->options(['TTL' => 600]);
    }

    private function title(): string
    {
        return $this->seat->timer_phase === StudyTimerPhase::Break
            ? '休息結束'
            : '專注時間結束';
    }

    private function body(): string
    {
        if ($this->seat->timer_phase === StudyTimerPhase::Break) {
            return '休息時間到了，準備好就開始下一輪專注吧。';
        }

        $round = $this->seat->timer_round;

        if ($round === null) {
            return '這次的專注時間到了，記得起來動一動。';
        }

        $cycle = PomodoroCycle::forProfile($this->seat->schedule?->studyRoomProfile);
        $breakMinutes = $cycle->breakMinutesAfterRound($round);

        return "第 {$round} 輪專注完成，該休息 {$breakMinutes} 分鐘了。";
    }
}
