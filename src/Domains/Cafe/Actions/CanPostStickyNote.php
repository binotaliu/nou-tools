<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use NouTools\Domains\Cafe\Exceptions\StickyNoteNotAllowedException;

/**
 * Whether the schedule could buy a note right now (acorns aside), for the
 * barista deciding whether to offer one.
 */
final readonly class CanPostStickyNote
{
    public function __construct(private CheckStickyNoteEligibility $checkStickyNoteEligibility) {}

    public function __invoke(int $scheduleId): bool
    {
        try {
            ($this->checkStickyNoteEligibility)($scheduleId);
        } catch (StickyNoteNotAllowedException) {
            return false;
        }

        return true;
    }
}
