<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CafeStickyNoteDrawingController extends Controller
{
    /**
     * Drawings are served only through here: a published note's to everyone,
     * a pending one's to its author and to signed-in staff.
     */
    public function __invoke(Request $request, CafeStickyNote $note): StreamedResponse
    {
        $viewer = $request->studentScheduleFromCookie();
        $onWall = $note->status === StickyNoteStatus::Published && $note->expires_at?->isFuture();
        $isAuthor = $viewer !== null && $viewer->id === $note->student_schedule_id;
        // Signed-in admin-panel users review held notes.
        $isStaff = $request->user() !== null;

        abort_unless($note->drawing_path !== null && ($onWall || $isAuthor || $isStaff), 404);

        return Storage::disk(CafeStickyNote::DRAWING_DISK)->response($note->drawing_path, null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }
}
