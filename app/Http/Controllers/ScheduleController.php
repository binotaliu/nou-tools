<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScheduleController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('schedule.index');
    }

    public function create(): \Illuminate\View\View
    {
        $sessionToken = session('schedule_token') ?: Str::uuid()->toString();
        session(['schedule_token' => $sessionToken]);

        return view('schedule.editor', [
            'sessionToken' => $sessionToken,
        ]);
    }

    public function edit(StudentSchedule $schedule): \Illuminate\View\View
    {
        $sessionToken = session('schedule_token') ?: Str::uuid()->toString();
        session(['schedule_token' => $sessionToken]);

        return view('schedule.editor', [
            'schedule' => $schedule,
            'sessionToken' => $sessionToken,
        ]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('q', '');
        $limit = $request->input('limit', 20);

        $courses = Course::query()
            ->where('name', 'like', "%{$query}%")
            ->with('classes')
            ->limit($limit)
            ->get();

        return response()->json($courses);
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

        $sessionToken = session('schedule_token') ?: Str::uuid()->toString();

        $schedule = StudentSchedule::create([
            'uuid' => Str::uuid(),
            'session_token' => $sessionToken,
            'name' => $validated['name'] ?? null,
        ]);

        foreach ($validated['items'] as $courseClassId) {
            StudentScheduleItem::create([
                'student_schedule_id' => $schedule->id,
                'course_class_id' => $courseClassId,
            ]);
        }

        // Treat requests with a JSON body as expecting JSON responses too (fetch doesn't always set Accept).
        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('schedule.show', $schedule),
            ]);
        }

        return redirect()->route('schedule.show', $schedule)
            ->with('success', '課表已保存！');
    }

    public function update(StudentSchedule $schedule, Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*' => 'required|exists:course_classes,id',
        ]);

        $schedule->update([
            'name' => $validated['name'],
        ]);

        $schedule->items()->delete();

        foreach ($validated['items'] as $courseClassId) {
            StudentScheduleItem::create([
                'student_schedule_id' => $schedule->id,
                'course_class_id' => $courseClassId,
            ]);
        }

        return redirect()->route('schedule.show', $schedule)
            ->with('success', '課表已更新！');
    }

    public function show(StudentSchedule $schedule): \Illuminate\View\View
    {
        $schedule->load(['items.courseClass.course', 'items.courseClass.schedules']);

        return view('schedule.show', [
            'schedule' => $schedule,
        ]);
    }

    public function calendar(StudentSchedule $schedule): \Illuminate\Http\Response
    {
        $schedule->load(['items.courseClass.schedules']);

        $ics = $this->generateICS($schedule);

        return response($ics)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="schedule.ics"');
    }

    private function generateICS(StudentSchedule $schedule): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Nou Tools//Schedule//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($schedule->items as $item) {
            $courseClass = $item->courseClass;
            $course = $courseClass->course;

            foreach ($courseClass->schedules as $classSchedule) {
                $dateStr = $classSchedule->date->format('Ymd');
                $startTime = $courseClass->start_time ? substr($courseClass->start_time, 0, 5) : '09:00';
                $endTime = $courseClass->end_time ? substr($courseClass->end_time, 0, 5) : '10:00';

                $startDateTime = $this->convertToICSDateTime($classSchedule->date, $startTime);
                $endDateTime = $this->convertToICSDateTime($classSchedule->date, $endTime);

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:'.$schedule->uuid.'-'.$item->id.'-'.$classSchedule->id.'@noutools.local';
                $lines[] = 'DTSTAMP:'.now()->format('Ymd\THis\Z');
                $lines[] = 'DTSTART:'.$startDateTime;
                $lines[] = 'DTEND:'.$endDateTime;
                $lines[] = 'SUMMARY:'.$this->escapeICSString($course->name.' ('.$courseClass->code.')');
                if ($courseClass->teacher_name) {
                    $lines[] = 'DESCRIPTION:'.$this->escapeICSString('Instructor: '.$courseClass->teacher_name);
                }
                if ($courseClass->link) {
                    $lines[] = 'URL:'.$courseClass->link;
                }
                $lines[] = 'END:VEVENT';
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    private function convertToICSDateTime(\DateTime $date, string $time): string
    {
        $timeParts = explode(':', $time);
        $hour = $timeParts[0] ?? '09';
        $minute = $timeParts[1] ?? '00';

        return $date->format('Ymd').'T'.str_pad($hour, 2, '0', STR_PAD_LEFT).str_pad($minute, 2, '0', STR_PAD_LEFT).'00Z';
    }

    private function escapeICSString(string $string): string
    {
        return str_replace(["\r\n", "\n", ',', ';', '\\'], ['\\n', '\\n', '\\,', '\\;', '\\\\'], $string);
    }
}
