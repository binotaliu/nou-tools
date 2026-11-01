<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Actions\RefundAcorns;

/**
 * An admin turns a note down: it never appears, and the acorns come back.
 */
final readonly class DeclineStickyNote
{
    public function __construct(
        private RefundAcorns $refundAcorns,
        private BroadcastCafeChange $broadcastCafeChange,
    ) {}

    public function __invoke(CafeStickyNote $note): CafeStickyNote
    {
        if ($note->status === StickyNoteStatus::Declined) {
            return $note;
        }

        $wasPublished = $note->status === StickyNoteStatus::Published;

        DB::transaction(function () use ($note): void {
            $note->status = StickyNoteStatus::Declined;
            $note->saveOrFail();

            $spend = $note->acornTransaction;

            if ($spend !== null) {
                ($this->refundAcorns)($spend);
            }
        });

        if ($wasPublished) {
            ($this->broadcastCafeChange)('note.removed');
        }

        return $note;
    }
}
