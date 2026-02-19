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
        $this->scheduleEvents = $scheduleEvents ?? $this->scheduleService->getUpcomingAndOngoingEvents();
        $this->countdownEvent = $countdownEvent ?? $this->scheduleService->getCountdownEvent();
    }

    public function render(): View
    {
        return view('components.school-calendar');
    }
}
