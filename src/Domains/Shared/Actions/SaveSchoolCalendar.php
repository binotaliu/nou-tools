<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\SchoolCalendarEvent;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Shared\DataTransferObjects\SchoolCalendarEventDTO;

final readonly class SaveSchoolCalendar
{
    /**
     * Replaces every event of a semester with the given list, so the admin
     * editor can add, change and remove rows in one save.
     *
     * @param  array<int, SchoolCalendarEventDTO>  $events
     */
    public function __invoke(string $term, array $events): void
    {
        DB::transaction(function () use ($term, $events): void {
            SchoolCalendarEvent::query()->forTerm($term)->delete();

            foreach ($events as $event) {
                (new SchoolCalendarEvent)->fillFromDTO($term, $event)->saveOrFail();
            }
        });
    }
}
