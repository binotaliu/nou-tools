<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use NouTools\Domains\Cafe\Actions\HoldStickyNoteForReview;
use NouTools\Domains\Cafe\Actions\ScreenStickyNote;
use Throwable;

/**
 * Runs the AI pre-screen for a newly bought sticky note on the real queue,
 * like the wiki's drafting. ScreenStickyNote handles model errors itself;
 * failed() covers what it cannot (a timeout, a killed worker) by holding the
 * note for an admin so it never sits in Screening forever.
 */
final class ScreenStickyNoteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public CafeStickyNote $note) {}

    public function handle(ScreenStickyNote $screenStickyNote): void
    {
        $screenStickyNote($this->note);
    }

    public function failed(Throwable $exception): void
    {
        $note = CafeStickyNote::query()->find($this->note->id);

        if ($note === null || $note->status !== StickyNoteStatus::Screening) {
            return;
        }

        app(HoldStickyNoteForReview::class)($note, ['verdict' => 'review', 'reason' => '審查工作中斷，請人工檢視。']);
    }
}
