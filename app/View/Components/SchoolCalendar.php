<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use NouTools\Domains\Shared\SchoolCalendar\Actions\ListUpcomingSchoolEvents;

final class SchoolCalendar extends Component
{
    /**
     * Raw upcoming/ongoing events (Y-m-d strings), rendered client-side —
     * see resources/views/components/school-calendar.blade.php.
     *
     * @var array<int, array{start: string, end: string, name: string, countdown: bool}>
     */
    public array $events;

    /**
     * Whether $events includes events that have already ended. True when a
     * non-current semester's full calendar was requested via $term.
     */
    public bool $showPastEvents;

    /**
     * Accept an optional override (keeps backwards compatibility when callers pass props).
     *
     * @param  array<int, array{start: string, end: string, name: string, countdown: bool}>|null  $events
     */
    public function __construct(?array $events = null, ?string $term = null, ?ListUpcomingSchoolEvents $eventsAction = null)
    {
        $this->events = $events ?? ($eventsAction ?? app(ListUpcomingSchoolEvents::class))(term: $term);
        $this->showPastEvents = $events === null && $term !== null && $term !== (string) config('app.current_semester');
    }

    public function render(): View
    {
        return view('components.school-calendar');
    }
}
