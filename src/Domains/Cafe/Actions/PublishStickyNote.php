<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use Illuminate\Support\Facades\Date;

/**
 * Puts a note on the wall for `lifetime_days` and tells the café.
 */
final readonly class PublishStickyNote
{
    public function __construct(private BroadcastCafeChange $broadcastCafeChange) {}

    /**
     * @param  array<string, mixed>|null  $verdict
     */
    public function __invoke(CafeStickyNote $note, ?array $verdict = null): CafeStickyNote
    {
        $now = Date::now();

        $note->status = StickyNoteStatus::Published;
        $note->published_at = $now;
        $note->expires_at = $now->copy()->addDays((int) config('cafe.notes.lifetime_days'));
        $note->ai_verdict = $verdict ?? $note->ai_verdict;
        $note->saveOrFail();

        ($this->broadcastCafeChange)('note.posted');

        return $note;
    }
}
