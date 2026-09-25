<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

use function Pest\Laravel\get;

it('renders a plain server-rendered backup of the schedule with its classroom links', function () {
    $term = (string) config('app.current_semester');
    $course = Course::factory()->create(['term' => $term, 'name' => '經濟學']);
    $courseClass = CourseClass::factory()->create([
        'course_id' => $course->id,
        'link' => 'https://classroom.example/main',
        'backup_classroom_url' => 'https://classroom.example/backup',
    ]);
    ClassSchedule::factory()->create(['class_id' => $courseClass->id]);

    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '我的備份課表']);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $courseClass->id,
    ]);

    $response = get(route('schedules.lite', $schedule));

    $response->assertSuccessful()
        ->assertSee('我的備份課表')
        ->assertSee('經濟學')
        ->assertSee('https://classroom.example/main', false)
        ->assertSee('https://classroom.example/backup', false)
        ->assertSee('noindex', false)
        // Not an Inertia page: no app shell, no build assets.
        ->assertDontSee('data-page', false)
        ->assertDontSee('/build/', false);
});

it('renders an empty schedule', function () {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '空課表']);

    get(route('schedules.lite', $schedule))
        ->assertSuccessful()
        ->assertSee('還沒有加入任何課程');
});

it('returns 404 for an unknown schedule', function () {
    get('/schedules/'.Str::uuid().'/lite')->assertNotFound();
});
