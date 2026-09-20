<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use NouTools\Domains\StudyRoom\Actions\ShowStudyRoomPage;

final class StudyRoomController extends Controller
{
    /**
     * Renders the 自習室 page shell only. The live seat map / timer / session
     * state (`roomState`) is deliberately NOT passed as an Inertia prop —
     * see AGENTS.md "自習室 (Study Room)" and
     * .github/skills/laravel-best-practices/rules/inertia-vue-views.md: it
     * changes too fast for Inertia's page-prop model, so the Vue page
     * fetches it itself from GET /study-room/state on mount and keeps it in
     * sync via the existing REST endpoints and Echo/Reverb broadcasts.
     */
    public function show(Request $request, ShowStudyRoomPage $showStudyRoomPage): Response
    {
        $viewModel = $showStudyRoomPage($request);

        return Inertia::render('StudyRoom/Show', [
            'hasSchedule' => $viewModel->hasSchedule,
            'needsProfile' => $viewModel->needsProfile,
            'emojiChoices' => $viewModel->emojiChoices,
            // HtmlString serializes to `{}` (an empty object) through
            // Inertia's JSON encoding, so it must be cast to a plain string
            // before it reaches the prop payload.
            'announcementHtml' => (string) $viewModel->announcementHtml,
            'openHoursLabel' => $viewModel->openHoursLabel,
            'isOpen' => $viewModel->isOpen,
            'subjects' => $viewModel->subjects,
            'verbs' => $viewModel->verbs,
            'profile' => $viewModel->profile,
            'clientConfig' => $viewModel->clientConfig,
            'vapidPublicKey' => $viewModel->vapidPublicKey,
        ]);
    }
}
