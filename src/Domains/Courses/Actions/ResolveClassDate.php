<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Turns a "MM/DD" string from the NOU course pages into a date for the term.
 *
 * A term is the ROC-year-in-AD plus A/B/C. A (fall) runs September through
 * January, so January/February roll over to the next calendar year; B and C
 * start in the calendar year after the term's year (B) or the same year (C).
 */
final class ResolveClassDate
{
    public function __invoke(string $dateString, string $term): ?CarbonInterface
    {
        $parts = explode('/', $dateString);

        if (count($parts) !== 2) {
            return null;
        }

        $month = (int) $parts[0];
        $day = (int) $parts[1];

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }

        $semester = substr($term, 4, 1);
        $year = (int) substr($term, 0, 4);

        if ($semester === 'B') {
            $year++;
        }

        if ($semester === 'A' && $month <= 2) {
            $year++;
        }

        return Date::create($year, $month, $day);
    }
}
