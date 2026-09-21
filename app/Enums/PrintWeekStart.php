<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Which weekday the printed sheet's calendars start their weeks on.
 */
enum PrintWeekStart: string
{
    case Monday = 'monday';
    case Sunday = 'sunday';

    /**
     * Anything but a known value falls back to Monday, the sheet's original layout.
     */
    public static function fromQuery(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::Monday) : self::Monday;
    }

    /**
     * @return array<int, string>
     */
    public function weekdayLabels(): array
    {
        $labels = ['一', '二', '三', '四', '五', '六', '日'];

        return match ($this) {
            self::Monday => $labels,
            self::Sunday => [$labels[6], ...array_slice($labels, 0, 6)],
        };
    }

    /**
     * How many blank cells precede the 1st of a month in its first week row.
     */
    public function leadingBlanks(CarbonInterface $firstOfMonth): int
    {
        return match ($this) {
            self::Monday => $firstOfMonth->isoWeekday() - 1,
            self::Sunday => $firstOfMonth->dayOfWeek,
        };
    }
}
