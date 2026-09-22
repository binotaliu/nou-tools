<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use NouTools\Domains\Schedules\ViewModels\SchedulePrintMonthViewModel;

/**
 * How many calendar columns the printed sheet uses. Three is the layout the
 * sheet is designed around and stays the answer for almost every schedule;
 * four is the fallback for a term whose per-date course lists are so long that
 * three columns would push the last row of months (and the QR code) off the
 * page, where the sheet clips them.
 *
 * The heights below are the print template's real sizes in mm (checked against
 * a rendered sheet), so keep them in step with `schedule/print.blade.php` and
 * `schedule/print/_month.blade.php`.
 */
final readonly class ResolvePrintMonthColumns
{
    private const int DEFAULT_COLUMNS = 3;

    private const int WIDE_COLUMNS = 4;

    /** From this many months on, the sheet always uses the wide layout. */
    private const int WIDE_FROM_MONTHS = 7;

    /** Height left for the month grid under the sheet header. */
    private const float GRID_HEIGHT = 180.0;

    private const float ROW_GAP = 3.18;

    /** The QR block, when it sits below the calendars, plus the gap above it. */
    private const float QR_BLOCK = 36.5;

    private const float CALENDAR_WIDTH = 185.0;

    private const float COLUMN_GAP = 6.35;

    /** Month title, weekday header and the space around the course list. */
    private const float MONTH_CHROME = 12.86;

    private const float WEEK_ROW = 5.0;

    private const float LIST_CHROME = 1.32;

    private const float LIST_ITEM_GAP = 0.26;

    private const float LIST_LINE = 3.09;

    /** A month with nothing on it prints one line saying so. */
    private const float EMPTY_LIST = 5.3;

    /** The class-date label column and the gap after it. */
    private const float LABEL_WIDTH = 14.5;

    /** One 7pt CJK character. */
    private const float EM = 2.47;

    /**
     * @param  array<int, SchedulePrintMonthViewModel>  $months
     */
    public function __invoke(array $months): int
    {
        if (count($months) >= self::WIDE_FROM_MONTHS) {
            return self::WIDE_COLUMNS;
        }

        $defaultHeight = $this->gridHeight($months, self::DEFAULT_COLUMNS);

        if ($defaultHeight <= self::GRID_HEIGHT) {
            return self::DEFAULT_COLUMNS;
        }

        return $this->gridHeight($months, self::WIDE_COLUMNS) < $defaultHeight
            ? self::WIDE_COLUMNS
            : self::DEFAULT_COLUMNS;
    }

    /**
     * Height of the calendars plus the QR block when it does not fit in the
     * grid's empty cell, in mm.
     *
     * @param  array<int, SchedulePrintMonthViewModel>  $months
     */
    private function gridHeight(array $months, int $columns): float
    {
        $weekRows = max(array_map(fn (SchedulePrintMonthViewModel $month) => count($month->weeks), $months) ?: [0]);
        $textUnits = $this->textUnitsPerLine($columns);
        $rows = array_chunk($months, $columns);

        $height = array_sum(array_map(
            fn (array $row) => max(array_map(
                fn (SchedulePrintMonthViewModel $month) => self::MONTH_CHROME + $weekRows * self::WEEK_ROW + $this->listHeight($month, $textUnits),
                $row,
            )),
            $rows,
        )) + (count($rows) - 1) * self::ROW_GAP;

        if (count($months) % $columns === 0) {
            $height += self::QR_BLOCK;
        }

        return $height;
    }

    private function listHeight(SchedulePrintMonthViewModel $month, int $textUnits): float
    {
        $items = count($month->classDays) + count($month->examDays);

        if ($items === 0) {
            return self::EMPTY_LIST;
        }

        $lines = count($month->examDays);

        foreach ($month->classDays as $day) {
            foreach ($day['courses'] as $course) {
                $lines += (int) ceil(mb_strwidth(trim($course['name'].' '.$course['time'])) / $textUnits);
            }
        }

        return self::LIST_CHROME + ($items - 1) * self::LIST_ITEM_GAP + $lines * self::LIST_LINE;
    }

    /**
     * How much course text fits on one line beside the date label, in
     * `mb_strwidth` units (a CJK character is two, a digit one).
     */
    private function textUnitsPerLine(int $columns): int
    {
        $columnWidth = (self::CALENDAR_WIDTH - ($columns - 1) * self::COLUMN_GAP) / $columns;

        return (int) floor(($columnWidth - self::LABEL_WIDTH) / self::EM * 2);
    }
}
