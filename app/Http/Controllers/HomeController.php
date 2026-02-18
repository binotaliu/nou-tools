<?php

namespace App\Http\Controllers;

use App\Services\SchoolScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private SchoolScheduleService $scheduleService
    ) {}

    public function index(Request $request): \Illuminate\View\View
    {
        // Use Taiwan time for greeting and default date
        $nowTaipei = Carbon::now('Asia/Taipei');

        // determine greeting
        $hour = (int) $nowTaipei->format('H');
        if ($hour >= 5 && $hour < 12) {
            $greeting = '早安';
        } elseif ($hour >= 12 && $hour < 18) {
            $greeting = '午安';
        } else {
            $greeting = '晚安';
        }

        // date to show / query (YYYY-MM-DD) — accept ?date=YYYY-MM-DD
        $dateParam = $request->query('date');
        try {
            $selectedDate = $dateParam ? Carbon::createFromFormat('Y-m-d', $dateParam, 'Asia/Taipei')->format('Y-m-d') : $nowTaipei->format('Y-m-d');
        } catch (\Exception $e) {
            $selectedDate = $nowTaipei->format('Y-m-d');
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
            'greeting' => $greeting,
            'nowTaipei' => $nowTaipei,
            'selectedDate' => $selectedDate,
            'courses' => $courses,
            'previousSchedule' => $previousSchedule,
            'scheduleEvents' => $this->scheduleService->getUpcomingAndOngoingEvents(),
            'countdownEvent' => $this->scheduleService->getCountdownEvent(),
        ]);
    }
}
