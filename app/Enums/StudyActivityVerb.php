<?php

declare(strict_types=1);

namespace App\Enums;

enum StudyActivityVerb: string
{
    case ExamPrep = 'exam_prep';
    case Reading = 'reading';
    case Homework = 'homework';
    case Review = 'review';

    public function label(): string
    {
        return match ($this) {
            self::ExamPrep => '正在準備…考試',
            self::Reading => '正在讀…',
            self::Homework => '寫…的作業',
            self::Review => '正在複習…',
        };
    }

    /**
     * Format the activity sentence shown next to a seat, e.g. "正在複習 普通物理學".
     */
    public function format(string $subject): string
    {
        return match ($this) {
            self::ExamPrep => "正在準備{$subject}考試",
            self::Reading => "正在讀{$subject}",
            self::Homework => "寫{$subject}的作業",
            self::Review => "正在複習{$subject}",
        };
    }

    public static function getLabels(): array
    {
        return array_reduce(self::cases(), function (array $carry, self $case): array {
            $carry[$case->value] = $case->label();

            return $carry;
        }, []);
    }
}
