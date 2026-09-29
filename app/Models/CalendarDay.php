<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CalendarDayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NouTools\Domains\Shared\DataTransferObjects\CalendarDayDTO;

/**
 * A per-date override for calendars: whether the date number is drawn red
 * (replacing the Saturday/Sunday default) and an optional note beside it.
 *
 * @property CarbonImmutable $date
 * @property bool $is_red
 * @property string|null $label
 */
final class CalendarDay extends Model
{
    /** @use HasFactory<CalendarDayFactory> */
    use HasFactory;

    public function fillFromDTO(CalendarDayDTO $data): self
    {
        $this->date = $data->date;
        $this->is_red = $data->isRed;
        $this->label = $data->label;

        return $this;
    }

    /**
     * @param  Builder<CalendarDay>  $query
     * @return Builder<CalendarDay>
     */
    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
    }

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date:Y-m-d',
            'is_red' => 'boolean',
        ];
    }
}
