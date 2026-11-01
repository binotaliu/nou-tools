<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use NouTools\Domains\Cafe\Exceptions\StickyNoteNotAllowedException;

/**
 * Why the schedule cannot buy a note right now, or null when it can (acorns
 * aside), so the page can stop them before they spend time drawing.
 */
final readonly class DescribeStickyNoteEligibility
{
    public function __construct(private CheckStickyNoteEligibility $checkStickyNoteEligibility) {}

    public function __invoke(int $scheduleId): ?string
    {
        try {
            ($this->checkStickyNoteEligibility)($scheduleId);
        } catch (StickyNoteNotAllowedException $exception) {
            return $exception->getMessage();
        }

        return null;
    }
}
