<?php

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use NouTools\Domains\Schedules\Actions\BuildScheduleEditorPage;
use NouTools\Domains\Schedules\Actions\BuildStudentScheduleCookie;
use NouTools\Domains\Schedules\Actions\CreateSchedule;
use NouTools\Domains\Schedules\Actions\ShowSchedulePage;
use NouTools\Domains\Schedules\Actions\UpdateSchedule;
use NouTools\Domains\Schedules\DataTransferObjects\StudentScheduleUpsertData;

class ScheduleController extends Controller
{
    public function create(Request $request, BuildScheduleEditorPage $buildScheduleEditorPage): View
    {
        $page = $buildScheduleEditorPage($request);

        return view('schedule.editor', [
            'courses' => $page->courses,
            'currentSemester' => $page->currentSemester,
            'previousSchedule' => $page->previousSchedule,
        ]);
    }

    public function edit(StudentSchedule $schedule, Request $request, BuildScheduleEditorPage $buildScheduleEditorPage): View
    {
        $page = $buildScheduleEditorPage($request, $schedule);

        return view('schedule.editor', [
            'schedule' => $page->schedule,
            'courses' => $page->courses,
            'currentSemester' => $page->currentSemester,
        ]);
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
                'redirect_url' => route('schedules.show', $schedule),
            ])->cookie($cookie);
        }

        return redirect()->route('schedules.show', $schedule)
            ->with('success', '課表已更新！')
            ->cookie($cookie);
    }

    public function show(StudentSchedule $schedule, ShowSchedulePage $showSchedulePage): View
    {
        return view('schedule.show', [
            'viewModel' => $showSchedulePage($schedule),
        ]);
    }
}
