<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;
use NouTools\Domains\Schedules\Actions\BuildScheduleAnnouncementsWidget;
use NouTools\Domains\Schedules\Actions\BuildScheduleEditorPage;
use NouTools\Domains\Schedules\Actions\BuildStudentScheduleCookie;
use NouTools\Domains\Schedules\Actions\CreateSchedule;
use NouTools\Domains\Schedules\Actions\ShowSchedulePage;
use NouTools\Domains\Schedules\Actions\UpdateSchedule;
use NouTools\Domains\Schedules\DataTransferObjects\StudentScheduleUpsertData;
use NouTools\Domains\Shared\SchoolCalendar\Actions\ListUpcomingSchoolEvents;

final class ScheduleController extends Controller
{
    public function create(Request $request, BuildScheduleEditorPage $buildScheduleEditorPage): Response
    {
        $page = $buildScheduleEditorPage($request);

        return Inertia::render('Schedule/Editor', ['viewModel' => $page]);
    }

    public function edit(StudentSchedule $schedule, Request $request, BuildScheduleEditorPage $buildScheduleEditorPage): Response
    {
        $page = $buildScheduleEditorPage($request, $schedule);

        return Inertia::render('Schedule/Editor', ['viewModel' => $page]);
    }

    public function store(StudentScheduleUpsertData $input, Request $request, CreateSchedule $createSchedule, BuildStudentScheduleCookie $buildStudentScheduleCookie): JsonResponse|RedirectResponse
    {
        $schedule = $createSchedule($input);
        $cookie = $buildStudentScheduleCookie($schedule);

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('schedules.show', $schedule),
            ])->cookie($cookie);
        }

        return redirect()->route('schedules.show', $schedule)
            ->with('success', '課表已保存！')
            ->cookie($cookie);
    }

    public function update(StudentSchedule $schedule, StudentScheduleUpsertData $input, Request $request, UpdateSchedule $updateSchedule, BuildStudentScheduleCookie $buildStudentScheduleCookie): JsonResponse|RedirectResponse
    {
        $schedule = $updateSchedule($schedule, $input);
        $cookie = $buildStudentScheduleCookie($schedule);

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('schedules.show', [$schedule, 'term' => $input->term]),
            ])->cookie($cookie);
        }

        return redirect()->route('schedules.show', [$schedule, 'term' => $input->term])
            ->with('success', '課表已更新！')
            ->cookie($cookie);
    }

    public function show(
        StudentSchedule $schedule,
        Request $request,
        ShowSchedulePage $showSchedulePage,
        ListUpcomingSchoolEvents $listUpcomingSchoolEvents,
        BuildScheduleAnnouncementsWidget $buildScheduleAnnouncementsWidget,
    ): Response {
        $linkedSchedule = $request->studentScheduleFromCookie();
        $viewModel = $showSchedulePage($schedule, $request->query('term'));

        $currentSemester = (string) config('app.current_semester');
        $showPastEvents = $viewModel->selectedTerm !== $currentSemester;

        $shareUrl = url(route('schedules.show', $viewModel->uuid));

        $range = config('app.current_semester_range', []);

        return Inertia::render('Schedule/Show', [
            'viewModel' => $viewModel,
            'shouldPromptRememberSchedule' => $linkedSchedule === null,
            'isLinkedSchedule' => $linkedSchedule?->id === $schedule->id,
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'greeting' => [
                'semesterLabel' => Str::toSemesterDisplay($currentSemester),
                'semesterCode' => $currentSemester,
                'semesterStart' => is_array($range) && ! empty($range[0]) ? (string) $range[0] : null,
                'semesterEnd' => is_array($range) && ! empty($range[1]) ? (string) $range[1] : null,
            ],
            'schoolCalendar' => [
                'events' => $listUpcomingSchoolEvents(term: $viewModel->selectedTerm),
                'showPastEvents' => $showPastEvents,
            ],
            'announcementsWidget' => $buildScheduleAnnouncementsWidget($schedule),
            'shareUrl' => $shareUrl,
            'qrCodeSvg' => DNS2D::getBarcodeSVG($shareUrl, 'QRCODE'),
        ]);
    }
}
