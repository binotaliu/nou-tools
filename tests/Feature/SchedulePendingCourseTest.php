<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use NouTools\Domains\Schedules\Actions\ImportCourseSelectSimulation;

function fakeCourseSelectSimJsonFor(string $term, string $courseName): string
{
    return json_encode([
        $term => [
            'calendar' => ['2026-09'],
            'courses' => [
                '1' => $courseName,
            ],
            'videoSessions' => [
                '1' => [
                    '上午班' => [
                        'dates' => ['2026-09-08', '2026-10-06'],
                        'start' => '9:00',
                        'end' => '10:50',
                    ],
                ],
            ],
        ],
    ]);
}

function mockCourseSelectSimJson(string $term, string $courseName): void
{
    File::shouldReceive('exists')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturnTrue()
        ->byDefault();

    File::shouldReceive('get')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturn(fakeCourseSelectSimJsonFor($term, $courseName))
        ->byDefault();
}

it('creates a schedule with a pending course-only item', function () {
    $course = Course::factory()->create(['term' => '2025B']);

    $payload = [
        'name' => 'Pending Schedule',
        'term' => '2025B',
        'items' => [
            ['course_id' => $course->id],
        ],
    ];

    $response = $this->postJson(route('schedules.store'), $payload);

    $response->assertStatus(200)->assertJson(['success' => true]);

    $this->assertDatabaseHas('student_schedule_items', [
        'course_id' => $course->id,
        'course_class_id' => null,
    ]);
});

it('creates a schedule mixing a resolved class and a pending course', function () {
    $courseClass = CourseClass::factory()->create();
    $pendingCourse = Course::factory()->create(['term' => '2025B']);

    $payload = [
        'name' => 'Mixed Schedule',
        'term' => '2025B',
        'items' => [
            ['course_id' => $courseClass->course_id, 'class_id' => $courseClass->id],
            ['course_id' => $pendingCourse->id],
        ],
    ];

    $response = $this->postJson(route('schedules.store'), $payload);

    $response->assertStatus(200)->assertJson(['success' => true]);

    $this->assertDatabaseHas('student_schedule_items', [
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);
    $this->assertDatabaseHas('student_schedule_items', [
        'course_id' => $pendingCourse->id,
        'course_class_id' => null,
    ]);
});

it('rejects a course id that does not exist in the given term', function () {
    $course = Course::factory()->create(['term' => '2025A']);

    $payload = [
        'name' => 'Bad Term',
        'term' => '2025B',
        'items' => [
            ['course_id' => $course->id],
        ],
    ];

    $this->postJson(route('schedules.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('items.0.course_id');
});

it('rejects a class id that belongs to a different course than the sibling course_id', function () {
    $courseA = Course::factory()->create(['term' => '2025B']);
    $courseB = Course::factory()->create(['term' => '2025B']);
    $classForB = CourseClass::factory()->create(['course_id' => $courseB->id]);

    $payload = [
        'name' => 'Mismatched Class',
        'term' => '2025B',
        'items' => [
            ['course_id' => $courseA->id, 'class_id' => $classForB->id],
        ],
    ];

    $this->postJson(route('schedules.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('items.0.class_id');
});

it('editor page lists courses without any classes yet', function () {
    Course::factory()->create(['name' => 'Map Only Course', 'term' => '2025B']);

    $response = $this->get(route('schedules.create', ['term' => '2025B']));

    $response->assertStatus(200)
        ->assertSee('Map Only Course')
        ->assertSee('&quot;has_classes&quot;:false', false);
});

it('updating a schedule replaces pending items scoped to the current term only', function () {
    config()->set('app.current_semester', '2026C');

    $currentCourse = Course::factory()->create(['term' => '2026C']);
    $otherTermCourse = Course::factory()->create(['term' => '2025B']);
    $newPendingCourse = Course::factory()->create(['term' => '2026C']);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Pending Cross Term',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $currentCourse->id,
        'course_class_id' => null,
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $otherTermCourse->id,
        'course_class_id' => null,
    ]);

    $response = $this->putJson(route('schedules.update', $schedule), [
        'name' => 'Pending Cross Term Updated',
        'term' => '2026C',
        'items' => [
            ['course_id' => $newPendingCourse->id],
        ],
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseMissing('student_schedule_items', [
        'student_schedule_id' => $schedule->id,
        'course_id' => $currentCourse->id,
    ]);
    $this->assertDatabaseHas('student_schedule_items', [
        'student_schedule_id' => $schedule->id,
        'course_id' => $newPendingCourse->id,
        'course_class_id' => null,
    ]);
    $this->assertDatabaseHas('student_schedule_items', [
        'student_schedule_id' => $schedule->id,
        'course_id' => $otherTermCourse->id,
    ]);
});

it('calendar generation skips class-session events for pending items but includes exam events', function () {
    $course = Course::factory()->create([
        'name' => 'Pending Exam Course',
        'midterm_date' => '2026-04-25',
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Pending Calendar',
        'display_options' => [
            'calendar_settings' => [
                'include_exams' => true,
            ],
        ],
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => null,
    ]);

    $response = $this->get(route('schedules.calendar', $schedule));

    $response->assertSuccessful()
        ->assertSee('SUMMARY:Pending Exam Course - 期中考')
        ->assertDontSee('SUMMARY:Pending Exam Course (');
});

it('schedule show page renders a pending item with a not-yet-assigned indicator', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create(['name' => 'Pending Show Course', 'term' => '2025B']);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Pending Show Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => null,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertSee('尚有未選擇班級的課程')
        ->assertSee('前往選擇班級');
});

it('schedule show markdown page shows placeholder columns for a pending item', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create(['name' => 'Pending Markdown Course', 'term' => '2025B']);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Pending Markdown Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => null,
    ]);

    $response = $this->get(route('schedules.show.md', $schedule));

    $response->assertStatus(200)
        ->assertSee('| Pending Markdown Course | 尚未分班 | 未提供 | 未提供 | 未提供 |', false);
});

it('rejects a duplicate course_id for the same schedule at the database level', function () {
    $course = Course::factory()->create();

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Duplicate Course',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => null,
    ]);

    expect(fn () => StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => null,
    ]))->toThrow(QueryException::class);
});

it('editor page shows a tentative class option for a pending course after importing notice data', function () {
    mockCourseSelectSimJson('2025B', 'Notice Session Course');
    Course::factory()->create(['name' => 'Notice Session Course', 'term' => '2025B']);
    (new ImportCourseSelectSimulation)('2025B');

    $response = $this->get(route('schedules.create', ['term' => '2025B']));

    $response->assertStatus(200)
        ->assertSee('&quot;has_classes&quot;:true', false)
        ->assertSee('&quot;is_tentative&quot;:true', false)
        ->assertSee('&quot;type&quot;:&quot;morning&quot;', false);
});

it('shows the chosen tentative class and notice disclaimer on the schedule show page', function () {
    config()->set('app.current_semester', '2025B');
    mockCourseSelectSimJson('2025B', 'Notice Show Course');

    $course = Course::factory()->create(['name' => 'Notice Show Course', 'term' => '2025B']);
    (new ImportCourseSelectSimulation)('2025B');

    $tentativeClass = CourseClass::query()->where('course_id', $course->id)->where('is_tentative', true)->firstOrFail();

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Notice Show Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $tentativeClass->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertSee('Notice Show Course')
        ->assertSee('尚未分班')
        ->assertDontSee('模擬資料');
});

it('shows the chosen tentative class in the markdown export', function () {
    config()->set('app.current_semester', '2025B');
    mockCourseSelectSimJson('2025B', 'Notice Markdown Course');

    $course = Course::factory()->create(['name' => 'Notice Markdown Course', 'term' => '2025B']);
    (new ImportCourseSelectSimulation)('2025B');

    $tentativeClass = CourseClass::query()->where('course_id', $course->id)->where('is_tentative', true)->firstOrFail();

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Notice Markdown Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $tentativeClass->id,
    ]);

    $response = $this->get(route('schedules.show.md', $schedule));

    $response->assertStatus(200)
        ->assertSee('選課注意事項：上午班', false)
        ->assertDontSee('模擬資料');
});

it('calendar export includes a notice-prefixed VEVENT per date for a chosen tentative class', function () {
    mockCourseSelectSimJson('2025B', 'Notice Calendar Course');

    $course = Course::factory()->create(['name' => 'Notice Calendar Course', 'term' => '2025B']);
    (new ImportCourseSelectSimulation)('2025B');

    $tentativeClass = CourseClass::query()->where('course_id', $course->id)->where('is_tentative', true)->firstOrFail();

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Notice Calendar Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $tentativeClass->id,
    ]);

    $response = $this->get(route('schedules.calendar', $schedule));

    $response->assertSuccessful()
        ->assertSee('SUMMARY:[選課注意事項] Notice Calendar Course (上午班)', false);

    expect(substr_count($response->getContent(), 'UID:course-'.$schedule->uuid))->toBe(2);
});

it('shows a pending course with a chosen tentative class under 面授日期', function () {
    config()->set('app.current_semester', '2025B');
    mockCourseSelectSimJson('2025B', 'Notice Class Dates Course');

    $course = Course::factory()->create(['name' => 'Notice Class Dates Course', 'term' => '2025B']);
    (new ImportCourseSelectSimulation)('2025B');

    $tentativeClass = CourseClass::query()->where('course_id', $course->id)->where('is_tentative', true)->firstOrFail();

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Notice Class Dates Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $tentativeClass->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertSee('面授日期')
        ->assertSee('Notice Class Dates Course')
        ->assertSee('尚未分班')
        ->assertSee('9:00 - 10:50');
});
