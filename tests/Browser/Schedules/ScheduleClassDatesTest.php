<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

// The 面授日期 card picks its default month from the browser's clock (in Taipei
// time) and swaps the visible month client-side, so it is only observable with
// a real browser.

function createScheduleWithClassesOn(array $courseNamesByDate): StudentSchedule
{
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Class Dates Schedule',
    ]);

    foreach ($courseNamesByDate as $date => $name) {
        $course = Course::factory()->create(['term' => '2025B', 'name' => $name]);
        $courseClass = CourseClass::factory()->create([
            'course_id' => $course->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        ClassSchedule::factory()->create([
            'class_id' => $courseClass->id,
            'date' => $date,
            'start_time' => null,
            'end_time' => null,
        ]);

        StudentScheduleItem::create([
            'student_schedule_id' => $schedule->id,
            'course_id' => $course->id,
            'course_class_id' => $courseClass->id,
        ]);
    }

    return $schedule;
}

it('shows one card that opens on the current month with a dot on each class date', function () {
    config()->set('app.current_semester', '2025B');

    $thisMonth = now('Asia/Taipei')->startOfMonth()->addDays(9);
    $laterMonth = now('Asia/Taipei')->startOfMonth()->addMonths(2)->addDays(4);

    $schedule = createScheduleWithClassesOn([
        $thisMonth->toDateString() => 'Alpha 本月課程',
        $laterMonth->toDateString() => 'Beta 之後的課程',
    ]);

    $list = '[data-testid="class-dates-list-'.$thisMonth->format('Y-m').'"]';

    visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->assertPresent('[data-testid="schedule-class-dates"]')
        ->assertAttribute('[data-testid="class-dates-month-'.$thisMonth->format('Y-m').'"]', 'aria-pressed', 'true')
        ->assertSeeIn($list, 'Alpha 本月課程')
        ->assertPresent('[data-testid="class-dates-day"][data-date="'.$thisMonth->toDateString().'"][data-has-classes="true"]')
        ->assertPresent('[data-testid="class-dates-day"][data-has-classes="false"]')
        ->assertNotPresent('[data-testid="class-dates-day"][data-date="'.$laterMonth->toDateString().'"]')
        ->assertMissing('[data-testid="class-dates-list-'.$laterMonth->format('Y-m').'"]');
});

it('reveals another month\'s courses when its month is chosen, including a month without classes', function () {
    config()->set('app.current_semester', '2025B');

    $thisMonth = now('Asia/Taipei')->startOfMonth()->addDays(9);
    $emptyMonth = $thisMonth->copy()->addMonth();
    $laterMonth = $thisMonth->copy()->addMonths(2)->addDays(-5);

    $schedule = createScheduleWithClassesOn([
        $thisMonth->toDateString() => 'Alpha 本月課程',
        $laterMonth->toDateString() => 'Beta 之後的課程',
    ]);

    visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->click('[data-testid="remember-schedule-dismiss"]')
        ->click('[data-testid="class-dates-month-'.$laterMonth->format('Y-m').'"]')
        ->assertSeeIn('[data-testid="class-dates-list-'.$laterMonth->format('Y-m').'"]', 'Beta 之後的課程')
        ->assertPresent('[data-testid="class-dates-day"][data-date="'.$laterMonth->toDateString().'"][data-has-classes="true"]')
        ->assertMissing('[data-testid="class-dates-list-'.$thisMonth->format('Y-m').'"]')
        ->click('[data-testid="class-dates-month-'.$emptyMonth->format('Y-m').'"]')
        ->assertSeeIn('[data-testid="schedule-class-dates"]', '這個月沒有面授')
        ->assertNotPresent('[data-testid="class-dates-day"][data-has-classes="true"]');
});

it('opens a semester that has already ended on its last month', function () {
    config()->set('app.current_semester', '2025B');

    $lastMonth = now('Asia/Taipei')->subMonths(3)->startOfMonth()->addDays(2);
    $earlierMonth = $lastMonth->copy()->subMonth();

    $schedule = createScheduleWithClassesOn([
        $earlierMonth->toDateString() => 'Alpha 較早的課程',
        $lastMonth->toDateString() => 'Beta 最後的課程',
    ]);

    visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->assertAttribute('[data-testid="class-dates-month-'.$lastMonth->format('Y-m').'"]', 'aria-pressed', 'true')
        ->assertSeeIn('[data-testid="class-dates-list-'.$lastMonth->format('Y-m').'"]', 'Beta 最後的課程');
});
