<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

/**
 * Text snippets shared by the course pages under vc.nou.edu.tw (the 一般生
 * vc1~vc5 pages and the 專班 svc pages): time ranges, per-session time
 * changes, teacher names and class dates.
 */
final class ParseNouClassText
{
    private const string TIME = '(\d{1,2}:\d{2}|\d{4})\s*[-~]\s*(\d{1,2}:\d{2}|\d{4})';

    private const array NUMERALS = [
        '一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5,
        '六' => 6, '七' => 7, '八' => 8, '九' => 9, '十' => 10,
    ];

    /**
     * The first time range in the text, as "H:MM"/"HH:MM" pairs; accepts
     * "09:00~10:50", "0900-1050" and similar.
     *
     * @return array{start: string, end: string}|null
     */
    public function time(string $text): ?array
    {
        if (preg_match('/'.self::TIME.'/', $text, $matches)) {
            return [
                'start' => $this->normalizeTime($matches[1]),
                'end' => $this->normalizeTime($matches[2]),
            ];
        }

        return null;
    }

    /**
     * Per-session time changes keyed by 1-based session number. Understands
     * "第3次：19:00-20:50" and "第二次改1400-1540" / "第一、二次改1550-1730".
     *
     * @return array<int, array{start_time: string, end_time: string}>
     */
    public function sessionTimeOverrides(string $text): array
    {
        $overrides = [];

        $pattern = '/第([\d一二三四五六七八九十、,]+)次(?:[：:]|改)\s*'.self::TIME.'/u';

        if (! preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            return $overrides;
        }

        foreach ($matches as $match) {
            foreach (preg_split('/[、,]/u', $match[1]) ?: [] as $sessionText) {
                $session = $this->sessionNumber(trim($sessionText));

                if ($session > 0) {
                    $overrides[$session] = [
                        'start_time' => $this->normalizeTime($match[2]),
                        'end_time' => $this->normalizeTime($match[3]),
                    ];
                }
            }
        }

        return $overrides;
    }

    public function teacherName(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        if (preg_match('/^(.+?老師)/', $text, $matches)) {
            return trim($matches[1]);
        }

        return trim($text);
    }

    /**
     * @return array<int, string> "MM/DD" strings in page order
     */
    public function dates(string $text): array
    {
        preg_match_all('/(\d{2}\/\d{2})/', $text, $matches);

        return $matches[1];
    }

    /**
     * "01.Course (上午班)" -> "Course". Returns '' when the title has no
     * numeric prefix.
     */
    public function courseName(string $titleText): string
    {
        if (! preg_match('/^\d+\.(.+)$/u', trim($titleText), $matches)) {
            return '';
        }

        $name = trim($matches[1]);

        return trim(preg_replace('/[（(][^（）()]*班[）)]$/u', '', $name) ?? $name);
    }

    private function sessionNumber(string $text): int
    {
        if (ctype_digit($text)) {
            return (int) $text;
        }

        return self::NUMERALS[$text] ?? 0;
    }

    private function normalizeTime(string $time): string
    {
        if (str_contains($time, ':')) {
            return $time;
        }

        return substr($time, 0, 2).':'.substr($time, 2, 2);
    }
}
