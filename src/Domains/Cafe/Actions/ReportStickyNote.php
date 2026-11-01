<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use App\Models\CafeStickyNoteReport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * A reader flags a published note. The session is the reader (there is no
 * login), counted once per session by a unique key on its hash; enough
 * distinct reports take the note off the wall for an admin to check.
 */
final readonly class ReportStickyNote
{
    public function __construct(private HoldStickyNoteForReview $holdStickyNoteForReview) {}

    /**
     * @return bool Whether this report was new (false = this session already reported it)
     */
    public function __invoke(CafeStickyNote $note, string $sessionId): bool
    {
        if ($note->status !== StickyNoteStatus::Published) {
            return false;
        }

        $created = DB::transaction(function () use ($note, $sessionId): bool {
            try {
                $report = new CafeStickyNoteReport;
                $report->cafe_sticky_note_id = $note->id;
                $report->session_hash = hash('sha256', $sessionId);
                $report->saveOrFail();
            } catch (QueryException $exception) {
                if ($exception->getCode() === '23000') {
                    return false;
                }

                throw $exception;
            }

            $note->increment('report_count');

            return true;
        });

        if ($created && $note->fresh()->report_count >= (int) config('cafe.notes.report_threshold')) {
            ($this->holdStickyNoteForReview)($note->fresh(), [
                ...($note->ai_verdict ?? []),
                'reports' => $note->fresh()->report_count,
            ]);
        }

        return $created;
    }
}
