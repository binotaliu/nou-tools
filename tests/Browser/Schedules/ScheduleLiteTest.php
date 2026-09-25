<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

// A class on a fixed Sunday-night slot far in the future (Taipei 19:00-21:00,
// Sunday 2099-01-04), so "upcoming" holds and the local conversion is
// deterministic. Asia/Kolkata (UTC+5:30, no DST) sees it at 16:30-18:30 on
// the same day.
$liteSchedule = function (): StudentSchedule {
    $course = Course::factory()->create(['term' => (string) config('app.current_semester'), 'name' => '跨時區課程']);
    $courseClass = CourseClass::factory()->create([
        'course_id' => $course->id,
        'start_time' => '19:00',
        'end_time' => '21:00',
    ]);
    ClassSchedule::factory()->create(['class_id' => $courseClass->id, 'date' => '2099-01-04', 'start_time' => null, 'end_time' => null]);

    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '時區課表']);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $courseClass->id,
    ]);

    return $schedule;
};

it('shows no local time line to viewers on Taipei time', function () use ($liteSchedule) {
    visit(route('schedules.lite', $liteSchedule(), false))
        ->withTimezone('Asia/Taipei')
        ->assertSee('跨時區課程')
        ->assertSee('下一堂：1/4 (日)')
        ->assertDontSee('你的時間')
        ->screenshot();
});

it("shows the class in an overseas viewer's own time", function () use ($liteSchedule) {
    visit(route('schedules.lite', $liteSchedule(), false))
        ->withTimezone('Asia/Kolkata')
        ->assertSee('你的時間 · 16:30 ~ 18:30 (GMT+5:30)')
        ->screenshot();
});

it("includes the local date when the viewer's day differs from Taipei's", function () use ($liteSchedule) {
    // Taipei Sunday 19:00 is Sunday 03:00 in UTC-8: same day. Use a zone
    // ahead of Taipei instead: Pacific/Kiritimati (UTC+14) sees Monday 01:00.
    visit(route('schedules.lite', $liteSchedule(), false))
        ->withTimezone('Pacific/Kiritimati')
        ->assertSee('你的時間 · 01/05 (一) 01:00 ~ 03:00 (GMT+14)')
        ->screenshot();
});
