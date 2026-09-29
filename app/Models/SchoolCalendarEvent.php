<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SchoolCalendarEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NouTools\Domains\Shared\DataTransferObjects\SchoolCalendarEventDTO;

/**
 * @property string $term
 * @property CarbonInterface $start_date
 * @property CarbonInterface $end_date
 * @property string $name
 * @property bool $is_countdown
 * @property bool $is_important
 */
final class SchoolCalendarEvent extends Model
{
    /** @use HasFactory<SchoolCalendarEventFactory> */
    use HasFactory;

    public function fillFromDTO(string $term, SchoolCalendarEventDTO $data): self
    {
        $this->term = $term;
        $this->start_date = $data->startDate;
        $this->end_date = $data->endDate;
        $this->name = $data->name;
        $this->is_countdown = $data->isCountdown;
        $this->is_important = $data->isImportant;

        return $this;
    }

    /**
     * @param  Builder<SchoolCalendarEvent>  $query
     * @return Builder<SchoolCalendarEvent>
     */
    public function scopeForTerm(Builder $query, string $term): Builder
    {
        return $query->where('term', $term);
    }

    /**
     * @param  Builder<SchoolCalendarEvent>  $query
     * @return Builder<SchoolCalendarEvent>
     */
    public function scopeImportant(Builder $query): Builder
    {
        return $query->where('is_important', true);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date:Y-m-d',
            'end_date' => 'immutable_date:Y-m-d',
            'is_countdown' => 'boolean',
            'is_important' => 'boolean',
        ];
    }
}
