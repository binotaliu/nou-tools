<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScheduleController extends Controller
{
    public function create(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $currentSemester = config('app.current_semester');
        $courses = Course::query()
            ->where('term', $currentSemester)
            ->with(['classes' => function ($query) {
                $query->orderBy('type');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'term' => $course->term,
                    'classes' => $course->classes->map(function ($class) {
                        return [
                            'id' => $class->id,
                            'code' => $class->code,
                            'type' => $class->type->value,
                            'type_label' => $class->type->label(),
                            'start_time' => $class->start_time,
                            'end_time' => $class->end_time,
                            'teacher_name' => $class->teacher_name,
                        ];
                    }),
                ];
            });

        // If the user explicitly requested a fresh/new schedule view, ignore cookie
        $previousSchedule = null;
        if (! $request->query('new')) {
            $cookie = $request->cookie('student_schedule');
            if ($cookie) {
                $data = json_decode($cookie, true);
                if (is_array($data) && isset($data['id'], $data['uuid'])) {
                    $model = StudentSchedule::find($data['id']);
                    if ($model) {
                        $previousSchedule = [
                            'id' => $model->id,
                            'uuid' => $model->uuid,
                            'token' => $model->getRouteKey(),
                            'name' => $model->name,
                        ];
                    }
                }
            }
        }

        return view('schedule.editor', [
            'courses' => $courses,
            'currentSemester' => $currentSemester,
            'semesterDisplay' => $this->formatSemesterDisplay($currentSemester),
            'previousSchedule' => $previousSchedule,
        ]);
    }

    public function edit(StudentSchedule $schedule): \Illuminate\View\View
    {
        $schedule->load(['items.courseClass.course']);

        $currentSemester = config('app.current_semester');
        $courses = Course::query()
            ->where('term', $currentSemester)
            ->with(['classes' => function ($query) {
                $query->orderBy('type');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'term' => $course->term,
                    'classes' => $course->classes->map(function ($class) {
                        return [
                            'id' => $class->id,
                            'code' => $class->code,
                            'type' => $class->type->value,
                            'type_label' => $class->type->label(),
                            'start_time' => $class->start_time,
                            'end_time' => $class->end_time,
                            'teacher_name' => $class->teacher_name,
                        ];
                    }),
                ];
            });

        return view('schedule.editor', [
            'schedule' => $schedule,
            'courses' => $courses,
            'currentSemester' => $currentSemester,
            'semesterDisplay' => $this->formatSemesterDisplay($currentSemester),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $rules = [
            'name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:course_classes,id',
        ];

        // If the client sent a JSON payload (e.g. fetch/axios), validate and return JSON errors.
        if ($request->isJson()) {
            $validator = \Illuminate\Support\Facades\Validator::make($request->json()->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();
        } else {
            $validated = $request->validate($rules);
        }

        $schedule = StudentSchedule::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $validated['name'] ?? null,
        ]);

        foreach ($validated['items'] as $courseClassId) {
            StudentScheduleItem::create([
                'student_schedule_id' => $schedule->id,
                'course_class_id' => $courseClassId,
            ]);
        }

        // persist schedule metadata into an encrypted, long-lived cookie so only backend can read it
        $cookieValue = json_encode([
            'id' => $schedule->id,
            'uuid' => $schedule->uuid,
            'name' => $schedule->name,
        ]);
        $cookie = cookie()->forever('student_schedule', $cookieValue);

        // Treat requests with a JSON body as expecting JSON responses too (fetch doesn't always set Accept).
        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('schedule.show', $schedule),
            ])->cookie($cookie);
        }

        return redirect()->route('schedule.show', $schedule)
            ->with('success', '課表已保存！')
            ->cookie($cookie);
    }

    public function update(StudentSchedule $schedule, Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $rules = [
            'name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:course_classes,id',
        ];

        // If the client sent a JSON payload (e.g. fetch/axios), validate and return JSON errors.
        if ($request->isJson()) {
            $validator = \Illuminate\Support\Facades\Validator::make($request->json()->all(), $rules);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validated = $validator->validated();
        } else {
            $validated = $request->validate($rules);
        }

        $schedule->update([
            'name' => $validated['name'] ?? null,
        ]);

        $schedule->items()->delete();

        foreach ($validated['items'] as $courseClassId) {
            StudentScheduleItem::create([
                'student_schedule_id' => $schedule->id,
                'course_class_id' => $courseClassId,
            ]);
        }

        // update the stored cookie so name/uuid stay in sync
        $cookieValue = json_encode([
            'id' => $schedule->id,
            'uuid' => $schedule->uuid,
            'name' => $schedule->name,
        ]);
        $cookie = cookie()->forever('student_schedule', $cookieValue);

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('schedule.show', $schedule),
            ])->cookie($cookie);
        }

        return redirect()->route('schedule.show', $schedule)
            ->with('success', '課表已更新！')
            ->cookie($cookie);
    }

    public function show(StudentSchedule $schedule): \Illuminate\View\View
    {
        $schedule->load(['items.courseClass.course', 'items.courseClass.schedules']);

        return view('schedule.show', [
            'schedule' => $schedule,
        ]);
    }

    /**
     * Format semester code to display format.
     * Example: 2025B → 114學年度下學期
     */
    private function formatSemesterDisplay(string $semester): string
    {
        // Extract year and term code
        preg_match('/^(\d{4})([ABC])$/', $semester, $matches);

        if (count($matches) !== 3) {
            return $semester;
        }

        $year = (int) $matches[1];
        $termCode = $matches[2];

        // Convert to ROC year (民國)
        $rocYear = $year - 1911;

        // Map term code to Chinese
        $termName = match ($termCode) {
            'A' => '上學期',
            'B' => '下學期',
            'C' => '暑期',
            default => $termCode,
        };

        return "{$rocYear}學年度{$termName}";
    }
}
