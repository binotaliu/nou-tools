<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;

/**
 * Takes a note off the public wall (or keeps it off) until an admin looks.
 */
final readonly class HoldStickyNoteForReview
{
    public function __construct(private BroadcastCafeChange $broadcastCafeChange) {}

    /**
     * @param  array<string, mixed>|null  $verdict
     */
    public function __invoke(CafeStickyNote $note, ?array $verdict = null): CafeStickyNote
    {
        $wasPublished = $note->status === StickyNoteStatus::Published;

        $note->status = StickyNoteStatus::PendingReview;
        $note->ai_verdict = $verdict ?? $note->ai_verdict;
        $note->saveOrFail();

        if ($wasPublished) {
            ($this->broadcastCafeChange)('note.removed');
        }

        return $note;
    }
}
