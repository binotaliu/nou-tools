<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\StudyRoom\Actions\ShowStudyRoomProgress;
use NouTools\Domains\StudyRoom\DataTransferObjects\ShowStudyRoomProgressData;

final class StudyRoomProgressController extends Controller
{
    public function __invoke(Request $request, ShowStudyRoomProgress $showStudyRoomProgress): Response|RedirectResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return to_route('study-room.show');
        }

        $data = ShowStudyRoomProgressData::parseQuery($request->query());

        return Inertia::render('StudyRoom/Progress', [
            'progress' => $showStudyRoomProgress($viewer, $data),
        ]);
    }
}
