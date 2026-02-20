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
            'previousSchedule' => $previousSchedule,
        ]);
    }
}
