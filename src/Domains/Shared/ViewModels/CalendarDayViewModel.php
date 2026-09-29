<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\ViewModels;

use App\Models\CalendarDay;
use Spatie\LaravelData\Data;

/**
 * A date whose colour or note overrides the calendar default.
 */
final class CalendarDayViewModel extends Data
{
    public function __construct(
        public string $date,
        public bool $isRed,
        public ?string $label,
    ) {}

    public static function fromModel(CalendarDay $day): self
    {
        return new self(
            date: $day->date->format('Y-m-d'),
            isRed: $day->is_red,
            label: $day->label,
        );
    }
}
