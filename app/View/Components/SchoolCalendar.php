<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use NouTools\Domains\SchoolCalendar\Actions\ListUpcomingSchoolEvents;

class SchoolCalendar extends Component
{
    /**
     * Array of schedule events (each item uses Carbon instances for start/end).
     */
    public array $scheduleEvents = [];

    /**
     * Optional countdown event (nearest upcoming/ongoing with countdown === true)
     */
    public ?array $countdownEvent = null;

    private ListUpcomingSchoolEvents $scheduleEventsAction;

    /**
     * Accept optional overrides for events (keeps backwards compatibility when callers pass props).
     */
    public function __construct(?array $scheduleEvents = null, ?array $countdownEvent = null, ?ListUpcomingSchoolEvents $scheduleEventsAction = null)
    {
        $this->scheduleEventsAction = $scheduleEventsAction ?? app(ListUpcomingSchoolEvents::class);

        $allEvents = $scheduleEvents ?? $this->scheduleEventsAction->getUpcomingAndOngoingEvents();
        $this->countdownEvent = $countdownEvent ?? $this->scheduleEventsAction->getCountdownEvent();

        // Filter out the countdown event from the schedule events list to avoid duplication
        if ($this->countdownEvent && is_array($allEvents)) {
            $this->scheduleEvents = array_filter($allEvents, fn ($event) => ! (
                $event['name'] === $this->countdownEvent['name'] &&
                $event['start']->format('Y-m-d') === $this->countdownEvent['start']->format('Y-m-d')
            ));
        } else {
            $this->scheduleEvents = $allEvents;
        }
    }

    public function render(): View
    {
        return view('components.school-calendar');
    }
}
