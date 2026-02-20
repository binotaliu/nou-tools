<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        // default date (Taipei timezone) — accept ?date=YYYY-MM-DD
        $dateParam = $request->query('date');
        try {
            $selectedDate = $dateParam
                ? Carbon::createFromFormat('Y-m-d', $dateParam, 'Asia/Taipei')->format('Y-m-d')
                : Carbon::now('Asia/Taipei')->format('Y-m-d');
        } catch (\Exception $e) {
            $selectedDate = Carbon::now('Asia/Taipei')->format('Y-m-d');
        }

        // load courses that have classes scheduled on the selected date
        $courses = \App\Models\Course::with(['classes' => function ($query) use ($selectedDate) {
            $query->with(['schedules' => function ($q) use ($selectedDate) {
                $q->whereDate('date', $selectedDate);
            }])->whereHas('schedules', function ($q) use ($selectedDate) {
                $q->whereDate('date', $selectedDate);
            });
        }])
            ->whereHas('classes.schedules', function ($query) use ($selectedDate) {
                $query->whereDate('date', $selectedDate);
            })
            ->get();

        // read encrypted cookie (only backend can read it)
        $previousSchedule = null;
        $cookie = $request->cookie('student_schedule');
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

        return view('home', [
            'selectedDate' => $selectedDate,
            'courses' => $courses,
            'previousSchedule' => $previousSchedule,
        ]);
    }
}
