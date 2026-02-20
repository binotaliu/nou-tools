<?php

namespace App\View\Components;

use App\Services\SchoolScheduleService;
use Illuminate\View\Component;
use Illuminate\View\View;

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

    private SchoolScheduleService $scheduleService;

    /**
     * Accept optional overrides for events (keeps backwards compatibility when callers pass props).
     */
    public function __construct(?array $scheduleEvents = null, ?array $countdownEvent = null, ?SchoolScheduleService $scheduleService = null)
    {
        $this->scheduleService = $scheduleService ?? app(SchoolScheduleService::class);

        // Use provided values when passed (from a view); otherwise load from service.
        $allEvents = $scheduleEvents ?? $this->scheduleService->getUpcomingAndOngoingEvents();
        $this->countdownEvent = $countdownEvent ?? $this->scheduleService->getCountdownEvent();

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
