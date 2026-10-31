<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use App\Models\StudyRoomSession;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * One slice of the subject pie chart: how much of the period's focus time
 * went to a subject (the course name or free label; 其他 when neither).
 */
final class StudyRoomSubjectShareViewModel extends Data
{
    public const string OTHER_LABEL = '其他';

    private const int MAX_SLICES = 6;

    public function __construct(
        public string $label,
        public int $focusSeconds,
        public int $percent,
    ) {}

    /**
     * Groups sessions by subject, largest first. Anything beyond the
     * largest slices is folded into 其他 so the legend stays readable.
     *
     * @param  array<int, StudyRoomSession>  $sessions
     * @return DataCollection<int, self>
     */
    public static function slicesFromSessions(array $sessions): DataCollection
    {
        $totals = [];
        foreach ($sessions as $session) {
            $label = $session->subject_label ?? $session->subjectCourse?->name ?? self::OTHER_LABEL;
            $totals[$label] = ($totals[$label] ?? 0) + $session->focus_seconds;
        }

        $named = array_filter($totals, fn (int $seconds, string $label): bool => $label !== self::OTHER_LABEL && $seconds > 0, ARRAY_FILTER_USE_BOTH);
        arsort($named);

        $other = $totals[self::OTHER_LABEL] ?? 0;
        $kept = array_slice($named, 0, self::MAX_SLICES - 1, true);
        $other += array_sum(array_slice($named, self::MAX_SLICES - 1, null, true));

        if ($other > 0) {
            $kept[self::OTHER_LABEL] = $other;
        }

        $sum = array_sum($kept);
        $slices = [];
        foreach ($kept as $label => $seconds) {
            $slices[] = new self((string) $label, $seconds, $sum > 0 ? (int) round($seconds / $sum * 100) : 0);
        }

        return self::collect($slices, DataCollection::class);
    }
}
