<?php

namespace App\Enums;

enum CourseClassType: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case FullRemote = 'full_remote';
    case MicroCredit = 'micro_credit';

    /**
     * @return array{start: string, end: string}|null
     */
    public function defaultTimeSlot(): ?array
    {
        return match ($this) {
            self::Morning => ['start' => '09:00', 'end' => '10:50'],
            self::Afternoon => ['start' => '14:00', 'end' => '15:50'],
            self::Evening => ['start' => '19:00', 'end' => '20:50'],
            self::FullRemote => null,
            self::MicroCredit => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Morning => '上午班',
            self::Afternoon => '下午班',
            self::Evening => '夜間班',
            self::FullRemote => '全遠距',
            self::MicroCredit => '微學分',
        };
    }

    /**
     * The section heading text in the vc4 HTML page.
     * Used to filter cards to the matching section.
     */
    public function sectionHeading(): ?string
    {
        return match ($this) {
            self::FullRemote => '全遠距',
            self::MicroCredit => '微學分',
            default => null,
        };
    }
}
