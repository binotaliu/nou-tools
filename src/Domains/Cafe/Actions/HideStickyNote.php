<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;

/**
 * Takes a published note down without refunding it (it was acceptable once,
 * or the author broke the rules).
 */
final readonly class HideStickyNote
{
    public function __construct(private BroadcastCafeChange $broadcastCafeChange) {}

    public function __invoke(CafeStickyNote $note): CafeStickyNote
    {
        $wasPublished = $note->status === StickyNoteStatus::Published;

        $note->status = StickyNoteStatus::Hidden;
        $note->saveOrFail();

        if ($wasPublished) {
            ($this->broadcastCafeChange)('note.removed');
        }

        return $note;
    }
}
