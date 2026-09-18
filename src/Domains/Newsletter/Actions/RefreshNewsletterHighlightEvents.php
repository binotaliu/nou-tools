<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use NouTools\Domains\Shared\SchoolCalendar\Actions\ListSchoolEventsBetween;

final readonly class RefreshNewsletterHighlightEvents
{
    public function __construct(
        private ListSchoolEventsBetween $listSchoolEventsBetween,
    ) {}

    /**
     * Re-snapshot the school calendar for the issue's highlight window, e.g.
     * after the calendar config was corrected or the window was changed.
     */
    public function __invoke(NewsletterIssue $issue): NewsletterIssue
    {
        $issue->highlights_events = ($this->listSchoolEventsBetween)(
            $issue->highlights_from->toDateString(),
            $issue->highlights_to->toDateString(),
        );
        $issue->saveOrFail();

        return $issue;
    }
}
