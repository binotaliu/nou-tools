<?php

namespace App\Http\Controllers;

use App\Models\StudentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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

    public function store(Request $request, CreateSchedule $createSchedule, BuildStudentScheduleCookie $buildStudentScheduleCookie): JsonResponse|RedirectResponse
    {
        $input = $this->resolveUpsertData($request);

        if ($input instanceof JsonResponse) {
            return $input;
        }

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

    public function update(StudentSchedule $schedule, Request $request, UpdateSchedule $updateSchedule, BuildStudentScheduleCookie $buildStudentScheduleCookie): JsonResponse|RedirectResponse
    {
        $input = $this->resolveUpsertData($request);

        if ($input instanceof JsonResponse) {
            return $input;
        }

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

    private function resolveUpsertData(Request $request): StudentScheduleUpsertData|JsonResponse
    {
        $payload = $request->isJson() ? $request->json()->all() : $request->all();
        $validator = Validator::make($payload, StudentScheduleUpsertData::rules());

        if ($validator->fails()) {
            if ($request->isJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            throw new ValidationException($validator);
        }

        return StudentScheduleUpsertData::from($validator->validated());
    }
}
