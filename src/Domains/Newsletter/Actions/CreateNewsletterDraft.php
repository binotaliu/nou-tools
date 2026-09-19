<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\NewsletterIssue;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Newsletter\DataTransferObjects\NewsletterIssueScheduleDTO;
use NouTools\Domains\Shared\SchoolCalendar\Actions\ListSchoolEventsBetween;

final readonly class CreateNewsletterDraft
{
    public function __construct(
        private ListSchoolEventsBetween $listSchoolEventsBetween,
    ) {}

    /**
     * Create the draft for a scheduled issue, snapshotting the school
     * calendar for its highlight window. Idempotent: an issue that already
     * exists for this key is returned untouched.
     */
    public function __invoke(NewsletterIssueScheduleDTO $schedule): NewsletterIssue
    {
        return DB::transaction(function () use ($schedule): NewsletterIssue {
            $existing = NewsletterIssue::query()
                ->where('issue_key', $schedule->issueKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $issue = new NewsletterIssue;
            $issue->fill([
                'issue_key' => $schedule->issueKey,
                'publishes_on' => $schedule->publishesOn->toDateString(),
                'covers_from' => $schedule->coversFrom->toDateString(),
                'covers_to' => $schedule->coversTo->toDateString(),
                'highlights_from' => $schedule->highlightsFrom->toDateString(),
                'highlights_to' => $schedule->highlightsTo->toDateString(),
                'highlights_events' => ($this->listSchoolEventsBetween)(
                    $schedule->highlightsFrom->toDateString(),
                    $schedule->highlightsTo->toDateString(),
                ),
            ]);
            $issue->saveOrFail();

            return $issue;
        });
    }
}
