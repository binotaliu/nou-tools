<?php

declare(strict_types=1);

namespace App\Enums;

enum BaristaTrigger: string
{
    case Greeting = 'greeting';
    case FirstVisit = 'first_visit';
    case TimeOfDay = 'time_of_day';
    case Weekend = 'weekend';
    case ExamUpcoming = 'exam_upcoming';
    case ExamDay = 'exam_day';
    case AfterExam = 'after_exam';
    case SchoolEventUpcoming = 'school_event_upcoming';
    case StudiedToday = 'studied_today';
    case AcornsLow = 'acorns_low';
    case AcornsMany = 'acorns_many';
    case SmallTalk = 'small_talk';
    case OrderThanks = 'order_thanks';
    case OfferStickyNote = 'offer_sticky_note';

    public function label(): string
    {
        return match ($this) {
            self::Greeting => '一般問候',
            self::FirstVisit => '第一次光臨',
            self::TimeOfDay => '依時段',
            self::Weekend => '週末',
            self::ExamUpcoming => '考試前',
            self::ExamDay => '考試當天',
            self::AfterExam => '考完之後',
            self::SchoolEventUpcoming => '校園行事曆事件',
            self::StudiedToday => '今天讀了一陣子',
            self::AcornsLow => '橡實不多',
            self::AcornsMany => '橡實很多',
            self::SmallTalk => '閒聊',
            self::OrderThanks => '點餐後的回應',
            self::OfferStickyNote => '點餐後推薦便利貼',
        };
    }

    /**
     * Triggers that only make sense right after ordering a drink.
     */
    public function isAfterOrder(): bool
    {
        return $this === self::OrderThanks || $this === self::OfferStickyNote;
    }
}
