<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ValueObjects;

use App\Enums\BaristaTrigger;

/**
 * Everything the barista may react to, gathered once per conversation.
 * Placeholders in a line ({course}, {days}...) come from `placeholders()`; a
 * line needing one that is null is simply not eligible.
 */
final readonly class BaristaContext
{
    /**
     * @param  'morning'|'afternoon'|'evening'|'late_night'  $period
     * @param  array{kind: string, label: string, days: int, course: string}|null  $exam  The nearest exam that has not passed (days 0 = today)
     * @param  array{kind: string, label: string, daysSince: int, course: string}|null  $recentExam  An exam that ended up to a few days ago
     * @param  array{name: string, days: int}|null  $event
     */
    public function __construct(
        public ?string $nickname,
        public string $period,
        public bool $isWeekend,
        public ?array $exam,
        public ?array $recentExam,
        public ?array $event,
        public int $studyMinutesToday,
        public int $acorns,
        public bool $isFirstVisit,
        public bool $canPostNote,
        public ?string $drink = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function placeholders(BaristaTrigger $trigger): array
    {
        $exam = $this->exam ?? null;
        $recent = $this->recentExam;

        return [
            'nickname' => $this->nickname,
            'acorns' => (string) $this->acorns,
            'study_minutes' => (string) $this->studyMinutesToday,
            'event' => $this->event['name'] ?? null,
            // {days} counts down to whichever the line is about.
            'days' => $trigger === BaristaTrigger::SchoolEventUpcoming
                ? (isset($this->event['days']) ? (string) $this->event['days'] : null)
                : (isset($exam['days']) ? (string) $exam['days'] : null),
            'course' => $exam['course'] ?? $recent['course'] ?? null,
            'exam' => $exam['label'] ?? $recent['label'] ?? null,
            'drink' => $this->drink,
        ];
    }
}
