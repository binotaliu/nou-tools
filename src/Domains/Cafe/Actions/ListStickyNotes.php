<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use NouTools\Domains\Cafe\ViewModels\StickyNoteViewModel;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * What the wall shows: the newest published notes still within their
 * lifetime, plus the viewer's own note while it is being screened or held
 * (nobody else sees those).
 */
final readonly class ListStickyNotes
{
    /**
     * @return array<int, StickyNoteViewModel>
     */
    public function __invoke(?StudentScheduleCookie $viewer): array
    {
        $viewerId = $viewer?->id;

        $published = CafeStickyNote::query()
            ->onWall()
            ->with('schedule.studyRoomProfile')
            ->orderByDesc('published_at')
            ->limit((int) config('cafe.notes.wall_limit'))
            ->get();

        $mine = $viewerId === null
            ? collect()
            : CafeStickyNote::query()
                ->where('student_schedule_id', $viewerId)
                ->whereIn('status', [StickyNoteStatus::Screening, StickyNoteStatus::PendingReview])
                ->with('schedule.studyRoomProfile')
                ->get();

        return $mine->concat($published)
            ->map(fn (CafeStickyNote $note): StickyNoteViewModel => StickyNoteViewModel::fromModel($note, $viewerId))
            ->all();
    }
}
