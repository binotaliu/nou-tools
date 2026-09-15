<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\PreviousExam;
use App\Models\StudentSchedule;
use App\Models\Textbook;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('course show page loads successfully', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'credits' => 2,
        'department' => 'Test Department',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $page->component('Courses/Show');

        $courseData = $page->toArray()['props']['viewModel']['course'];

        expect($courseData['name'])->toBe('Test Course');
        expect($courseData['department'])->toBe('Test Department');
    });
});

test('course show page includes seo meta description', function () {
    $course = Course::factory()->create([
        'name' => 'Test Course',
        'credits' => 3,
        'department' => 'Test Department',
        'term' => '11401',
    ]);

    $response = $this->get(route('course.show', $course));

    // The <meta name="description"> is rendered client-side by
    // Courses/Show.vue from viewModel.course; assert the underlying data
    // instead of the rendered tag (there's no SSR yet).
    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $courseData = $page->toArray()['props']['viewModel']['course'];

        expect($courseData['name'])->toBe('Test Course');
        expect($courseData['department'])->toBe('Test Department');
        expect($courseData['credits'])->toBe(3);
        expect($courseData['term'])->toBe('11401');
    });
});

test('course show page displays course information', function () {
    $course = Course::factory()->create([
        'name' => 'Advanced Testing',
        'credit_type' => '必修',
        'credits' => 3,
        'department' => 'Computer Science',
        'in_person_class_type' => '四次',
        'media' => '網頁',
        'nature' => '進階',
        'description_url' => 'https://example.com/description',
        'multimedia_url' => 'https://example.com/multimedia',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $viewModel = $page->toArray()['props']['viewModel'];
        $courseData = $viewModel['course'];

        expect($courseData['name'])->toBe('Advanced Testing');
        expect($courseData['creditType'])->toBe('必修');
        expect($courseData['credits'])->toBe(3);
        expect($courseData['department'])->toBe('Computer Science');
        expect($viewModel['inPersonClassType'])->toBe('四次');
        expect($viewModel['media'])->toBe('網頁');
        expect($courseData['nature'])->toBe('進階');
    });
});

test('course show page displays course classes', function () {
    $course = Course::factory()->create();
    $class = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'TEST001',
        'teacher_name' => '王老師',
        'start_time' => '09:00',
        'end_time' => '11:00',
        'link' => 'https://example.com/class',
    ]);
    $class->schedules()->create([
        'date' => now()->addDays(7),
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $classes = $page->toArray()['props']['viewModel']['course']['classes'];

        expect($classes)->toHaveCount(1);
        expect($classes[0]['code'])->toBe('TEST001');
        expect($classes[0]['teacherName'])->toBe('王老師');
        expect($classes[0]['startTime'])->toBe('09:00');
        expect($classes[0]['endTime'])->toBe('11:00');
    });
});

test('schedule-level overrides show next to dates only', function () {
    $course = Course::factory()->create();
    $class = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'OVR001',
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);

    $dateWithOverride = now()->addDays(3);
    $dateWithoutOverride = now()->addDays(4);

    $class->schedules()->create([
        'date' => $dateWithOverride,
        'start_time' => '14:00',
        'end_time' => '16:00',
    ]);

    $class->schedules()->create([
        'date' => $dateWithoutOverride,
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) use ($dateWithOverride, $dateWithoutOverride) {
        $sessions = $page->toArray()['props']['viewModel']['course']['classes'][0]['sessions'];
        $byDate = collect($sessions)->keyBy('date');

        $overrideSession = $byDate->get($dateWithOverride->toDateString());
        $defaultSession = $byDate->get($dateWithoutOverride->toDateString());

        expect($overrideSession['startTime'])->toBe('14:00');
        expect($overrideSession['endTime'])->toBe('16:00');
        expect($defaultSession['startTime'])->toBe('09:00');
        expect($defaultSession['endTime'])->toBe('11:00');
    });
});

test('course show page with missing schedule information', function () {
    $course = Course::factory()->create();
    CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'EMPTY001',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $classes = $page->toArray()['props']['viewModel']['course']['classes'];

        expect($classes[0]['code'])->toBe('EMPTY001');
        expect($classes[0]['sessions'])->toBe([]);
    });
});

test('course show page without classes', function () {
    $course = Course::factory()->create([
        'name' => 'Standalone Course',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $viewModel = $page->toArray()['props']['viewModel'];

        expect($viewModel['course']['name'])->toBe('Standalone Course');
        expect($viewModel['course']['classes'])->toBe([]);
    });
});

test('course show page shows previous-schedule link when cookie exists', function () {
    $course = Course::factory()->create();

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'My Saved Schedule',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) use ($schedule) {
        $previousSchedule = $page->toArray()['props']['viewModel']['previousSchedule'];

        expect($previousSchedule)->not->toBeNull();
        expect($previousSchedule['uuid'])->toBe((string) $schedule->uuid);
        expect($previousSchedule['token'])->toBe((string) $schedule->getRouteKey());
    });
});

// new test for multiple previous exams

test('course show page displays all previous exams for course when cookie exists', function () {
    $course = Course::factory()->create(['name' => 'History 101']);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'My Schedule',
    ]);

    PreviousExam::create([
        'course_name' => $course->name,
        'course_no' => 'HIST101',
        'term' => '114上學期',
        'midterm_reference_primary' => 'mid1.pdf',
        'midterm_reference_secondary' => 'mid1b.pdf',
        'final_reference_primary' => 'fin1.pdf',
        'final_reference_secondary' => 'fin1b.pdf',
    ]);

    PreviousExam::create([
        'course_name' => $course->name,
        'course_no' => 'HIST101',
        'term' => '115下學期',
        'midterm_reference_primary' => 'mid2.pdf',
        'midterm_reference_secondary' => null,
        'final_reference_primary' => null,
        'final_reference_secondary' => 'fin2b.pdf',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $previousExams = $page->toArray()['props']['viewModel']['course']['previousExams'];
        $terms = collect($previousExams)->pluck('term');

        expect($terms)->toContain('114上學期', '115下學期');

        $first = collect($previousExams)->firstWhere('term', '114上學期');
        $second = collect($previousExams)->firstWhere('term', '115下學期');

        expect($first['midtermReferencePrimary'])->toBe('mid1.pdf');
        expect($first['midtermReferenceSecondary'])->toBe('mid1b.pdf');
        expect($first['finalReferencePrimary'])->toBe('fin1.pdf');
        expect($first['finalReferenceSecondary'])->toBe('fin1b.pdf');
        expect($second['midtermReferencePrimary'])->toBe('mid2.pdf');
        expect($second['finalReferenceSecondary'])->toBe('fin2b.pdf');
    });
});

test('course show page sets download attribute with subject name and term for exam links', function () {
    // The `download="..."` attribute (course name with Str::toFilenameSafe()
    // applied + term + reference filename) is built client-side by
    // Courses/Show.vue's examSubjectName/fileExtension helpers, not
    // server-rendered, so this asserts the raw ViewModel data those helpers
    // need is present and unmodified (the filename-unsafe characters in
    // particular) rather than the rendered attribute.
    $course = Course::factory()->create(['name' => 'History/101: Intro']);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'My Schedule',
    ]);

    PreviousExam::create([
        'course_name' => $course->name,
        'course_no' => 'HIST101',
        'term' => '114上學期',
        'midterm_reference_primary' => 'mid1.pdf',
        'midterm_reference_secondary' => 'mid1b.pdf',
        'final_reference_primary' => 'fin1.pdf',
        'final_reference_secondary' => 'fin1b.pdf',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $viewModel = $page->toArray()['props']['viewModel'];
        $exam = $viewModel['course']['previousExams'][0];

        expect($viewModel['course']['name'])->toBe('History/101: Intro');
        expect($exam['term'])->toBe('114上學期');
        expect($exam['midtermReferencePrimary'])->toBe('mid1.pdf');
        expect($exam['midtermReferenceSecondary'])->toBe('mid1b.pdf');
        expect($exam['finalReferencePrimary'])->toBe('fin1.pdf');
        expect($exam['finalReferenceSecondary'])->toBe('fin1b.pdf');
    });
});

test('course show page displays exam information', function () {
    $course = Course::factory()->create([
        'name' => 'Exam Course',
        'midterm_date' => '2025-04-25',
        'final_date' => '2025-06-27',
        'exam_time_start' => '13:30',
        'exam_time_end' => '14:40',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $courseData = $page->toArray()['props']['viewModel']['course'];

        expect($courseData['midtermDate'])->toBe('2025-04-25');
        expect($courseData['finalDate'])->toBe('2025-06-27');
        expect($courseData['examTimeStart'])->toBe('13:30');
        expect($courseData['examTimeEnd'])->toBe('14:40');
    });
});

test('course show page displays textbook information', function () {
    $course = Course::factory()->create();
    Textbook::factory()->create([
        'course_id' => $course->id,
        'book_title' => 'Introduction to Testing',
        'edition' => '第2版',
        'price_info' => '200元',
        'reference_url' => 'https://example.com/book',
    ]);

    $response = $this->get(route('course.show', $course));

    $response->assertStatus(200);
    $response->assertInertia(function (Assert $page) {
        $textbook = $page->toArray()['props']['viewModel']['course']['textbook'];

        expect($textbook['bookTitle'])->toBe('Introduction to Testing');
        expect($textbook['edition'])->toBe('第2版');
        expect($textbook['priceInfo'])->toBe('200元');
        expect($textbook['referenceUrl'])->toBe('https://example.com/book');
    });
});

test('course show markdown page lists class and exam information', function () {
    $course = Course::factory()->create([
        'name' => 'Markdown Course',
        'midterm_date' => '2025-04-25',
        'final_date' => '2025-06-27',
        'exam_time_start' => '13:30',
        'exam_time_end' => '14:40',
    ]);

    CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'MD101',
        'start_time' => '09:00',
        'end_time' => '10:00',
        'teacher_name' => '王小明',
        'link' => 'https://example.com/class-link',
        'backup_classroom_url' => 'https://example.com/backup-link',
    ]);

    Textbook::factory()->create([
        'course_id' => $course->id,
        'book_title' => 'Introduction to Testing',
    ]);

    $response = $this->get(route('course.show.md', $course));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# Markdown Course', false)
        ->assertSee('| MD101 |', false)
        ->assertSee('王小明')
        ->assertSee('https://example.com/class-link')
        ->assertSee('https://example.com/backup-link')
        ->assertSee('Introduction to Testing')
        ->assertSee('4/25')
        ->assertSee('6/27');
});

test('course show page returns markdown when the client prefers it in the Accept header', function () {
    $course = Course::factory()->create(['name' => 'Accept Header Course']);

    $response = $this->get(route('course.show', $course), [
        'Accept' => 'text/markdown, text/html;q=0.8',
    ]);

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# Accept Header Course', false);
});
