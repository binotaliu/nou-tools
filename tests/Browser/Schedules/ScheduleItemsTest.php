<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// "下次上課" and the row order are computed in the browser from the viewer's
// local clock (see window.nouToolsScheduleItems in resources/js/app.js), so this
// behaviour, and the "你的時間" hint shown to overseas students, are only
// observable with a real browser.

$createScheduleWithClass = function (string $date, string $startTime, string $endTime): array {
    $course = Course::factory()->create(['term' => '2025B']);
    $courseClass = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'BRW101',
        'start_time' => $startTime,
        'end_time' => $endTime,
    ]);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => $date,
        'start_time' => null,
        'end_time' => null,
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Browser Test Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    return [$schedule, $course, $courseClass];
};

it('shows the Taipei class time without a "your time" hint for a viewer in Asia/Taipei', function () use ($createScheduleWithClass) {
    config()->set('app.current_semester', '2025B');

    $date = now('Asia/Taipei')->addDays(3)->toDateString();
    [$schedule, $course] = $createScheduleWithClass($date, '09:00', '10:00');

    $classDate = Carbon::parse($date, 'Asia/Taipei');

    visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->assertSee($course->name)
        ->assertSee($classDate->format('n').'/'.$classDate->format('j').' ('.chineseWeekdayChar($classDate).')')
        ->assertSee('09:00 ~ 10:00')
        ->assertDontSee('你的時間');
});

it('shows a "your time" hint with the converted class time for a viewer whose timezone differs from Asia/Taipei', function () use ($createScheduleWithClass) {
    config()->set('app.current_semester', '2025B');

    $date = now('Asia/Taipei')->addDays(3)->toDateString();
    [$schedule, $course] = $createScheduleWithClass($date, '09:00', '10:00');

    // Fixed-offset zone (no DST), 2.5 hours behind Taipei, same calendar day.
    $timezone = 'Asia/Kolkata';
    $instantStart = Carbon::parse($date.' 09:00', 'Asia/Taipei')->clone()->setTimezone($timezone);
    $instantEnd = Carbon::parse($date.' 10:00', 'Asia/Taipei')->clone()->setTimezone($timezone);

    visit(route('schedules.show', $schedule))
        ->withTimezone($timezone)
        ->assertSee($course->name)
        ->assertSee(
            '你的時間 · '.$instantStart->format('H:i').' ~ '.$instantEnd->format('H:i').' ('.gmtLabelForOffset($instantStart->utcOffset()).')'
        )
        ->screenshot();
});

it('shows the local calendar date in the "your time" hint when it crosses to the previous day', function () use ($createScheduleWithClass) {
    config()->set('app.current_semester', '2025B');

    $date = now('Asia/Taipei')->addDays(3)->toDateString();
    [$schedule, $course] = $createScheduleWithClass($date, '07:00', '08:00');

    // Fixed UTC-7 offset, 15 hours behind Taipei: 07:00 Taipei falls on the
    // previous calendar day for this viewer.
    $timezone = 'America/Phoenix';
    $instantStart = Carbon::parse($date.' 07:00', 'Asia/Taipei')->clone()->setTimezone($timezone);
    $instantEnd = Carbon::parse($date.' 08:00', 'Asia/Taipei')->clone()->setTimezone($timezone);

    expect($instantStart->toDateString())->not->toBe($date);

    visit(route('schedules.show', $schedule))
        ->withTimezone($timezone)
        ->assertSee($course->name)
        ->assertSee(
            '你的時間 · '.$instantStart->format('n').'/'.$instantStart->format('j').' ('.chineseWeekdayChar($instantStart).') '
            .$instantStart->format('H:i').' ~ '.$instantEnd->format('H:i').' ('.gmtLabelForOffset($instantStart->utcOffset()).')'
        )
        ->screenshot();
});

it('shows "無未來課程" when a course only has past class occurrences', function () {
    config()->set('app.current_semester', '2025B');

    $pastDate = now('Asia/Taipei')->subDays(3)->toDateString();

    $course = Course::factory()->create(['term' => '2025B']);
    $courseClass = CourseClass::factory()->create([
        'course_id' => $course->id,
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => $pastDate,
        'start_time' => null,
        'end_time' => null,
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Past Only Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->assertSee($course->name)
        ->assertSee('無未來課程')
        ->screenshot();
});

it('renders the next class as a card on a phone, with the date tile, time and classroom link', function () use ($createScheduleWithClass) {
    config()->set('app.current_semester', '2025B');

    $date = now('Asia/Taipei')->addDays(3)->toDateString();
    [$schedule, $course, $courseClass] = $createScheduleWithClass($date, '09:00', '10:00');
    $courseClass->update(['link' => 'https://example.com/live', 'backup_classroom_url' => 'https://example.com/backup']);

    $classDate = Carbon::parse($date, 'Asia/Taipei');

    $page = visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->resize(390, 844)
        ->assertSee($course->name);

    $card = '[data-testid="schedule-item-card"]';

    $page->assertVisible($card)
        ->assertSeeIn($card, $classDate->format('n').'/'.$classDate->format('j'))
        ->assertSeeIn($card, chineseWeekdayChar($classDate))
        ->assertSeeIn($card, '09:00 ~ 10:00')
        ->assertSeeIn($card, '進入教室')
        ->assertSeeIn($card, '備用教室')
        ->assertDontSee('進行中');
});

it('summarises the next class with accesskeys for the summary, the classroom, the timetable and the term', function () use ($createScheduleWithClass) {
    config()->set('app.current_semester', '2025B');

    $date = now('Asia/Taipei')->addDays(3)->toDateString();
    [$schedule, $course, $courseClass] = $createScheduleWithClass($date, '09:00', '10:00');
    $courseClass->update(['link' => 'https://example.com/live']);

    $page = visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->assertSee($course->name)
        ->assertSeeIn('[data-testid="schedule-next-class"]', '下一堂課')
        ->assertSeeIn('[data-testid="schedule-next-class-name"]', $course->name)
        ->assertSeeIn('[data-testid="schedule-next-class"]', '09:00 ~ 10:00')
        ->assertAttribute('[data-testid="schedule-next-class-join"]', 'href', 'https://example.com/live');

    $keys = json_decode($page->script(
        "JSON.stringify(Object.fromEntries([...document.querySelectorAll('main [accesskey]')].map(e => [e.accessKey, e.id || e.dataset.testid])))"
    ), true);

    expect($keys)->toBe([
        '4' => 'schedule-next-class',
        '5' => 'schedule-next-class-join',
        '6' => 'schedule-items-heading',
        '7' => 'term',
    ]);
});

it('offers no next-class accesskeys when the schedule has no upcoming class', function () use ($createScheduleWithClass) {
    config()->set('app.current_semester', '2025B');

    $date = now('Asia/Taipei')->subDays(3)->toDateString();
    [$schedule] = $createScheduleWithClass($date, '09:00', '10:00');

    $page = visit(route('schedules.show', $schedule))
        ->withTimezone('Asia/Taipei')
        ->assertSeeIn('[data-testid="schedule-next-class"]', '無未來課程');

    $keys = json_decode($page->script(
        "JSON.stringify([...document.querySelectorAll('main [accesskey]')].map(e => e.accessKey))"
    ), true);

    expect($keys)->not->toContain('4')->not->toContain('5');
});
