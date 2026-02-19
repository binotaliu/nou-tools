<?php

namespace App\Http\Controllers;

use App\Models\Course;

class CourseController extends Controller
{
    public function show(Course $course): \Illuminate\View\View
    {
        $course->load(['classes' => function ($query) {
            $query->with('schedules')->orderBy('type');
        }]);

        // read stored student_schedule cookie (if present) so views can link back to the user's saved schedule
        $previousSchedule = null;
        $cookie = request()->cookie('student_schedule');
        if ($cookie) {
            $data = json_decode($cookie, true);
            if (is_array($data) && isset($data['id'], $data['uuid'])) {
                $model = \App\Models\StudentSchedule::find($data['id']);
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

        return view('course.show', [
            'course' => $course,
            'semesterDisplay' => $this->formatSemesterDisplay((string) $course->term),
            'previousSchedule' => $previousSchedule,
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

        return "{$rocYear} 學年度{$termName}";
    }
}
