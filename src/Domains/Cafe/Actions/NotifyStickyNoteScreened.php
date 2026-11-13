<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeStickyNote;
use App\Notifications\StickyNoteScreened;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Tells the admins' Discord about a new note, published or held. A webhook
 * error is logged, never thrown, so it cannot undo the screening.
 */
final readonly class NotifyStickyNoteScreened
{
    public function __invoke(CafeStickyNote $note): void
    {
        $webhook = config('services.discord.webhooks.sticky_notes');

        if (blank($webhook)) {
            return;
        }

        try {
            Notification::route('discord-webhook', $webhook)
                ->notifyNow(new StickyNoteScreened($note->loadMissing('schedule.studyRoomProfile')));
        } catch (Throwable $exception) {
            Log::warning('Sticky note webhook failed.', ['note' => $note->id, 'exception' => $exception->getMessage()]);
        }
    }
}
