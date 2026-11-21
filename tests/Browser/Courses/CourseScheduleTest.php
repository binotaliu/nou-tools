<?php

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;

// Grouping (考試時間 / 學系 / 學分數), the 一般課程 vs 微學分與全遠距 split, and
// the resulting table columns are all computed client-side from the Inertia
// props (see resources/js/Pages/Courses/Schedule.vue), so this behaviour is
// only observable with a real browser rather than the server-rendered
// Feature test. Switch the dropdown via the `[data-testid="group-by-select"]`
// selector, not its visible label text, to avoid ambiguity with the
// `<option>` text nodes.

it('groups general courses by exam time slot and keeps micro-credit/remote courses in a separate section by default', function () {
    $term = config('app.current_semester');
    $finalDate = now()->next('Saturday');

    Course::factory()->create([
        'name' => 'Course A',
        'term' => $term,
        'final_date' => $finalDate,
        'exam_time_start' => '15:00',
        'exam_time_end' => '16:10',
    ]);
    Course::factory()->create([
        'name' => 'Course B',
        'term' => $term,
        'final_date' => $finalDate,
        'exam_time_start' => '15:00',
        'exam_time_end' => '16:10',
    ]);

    $microCreditCourse = Course::factory()->create([
        'name' => 'Micro Credit Course',
        'term' => $term,
    ]);
    CourseClass::factory()->create([
        'course_id' => $microCreditCourse->id,
        'type' => CourseClassType::MicroCredit,
    ]);

    $page = visit(route('course.schedule'));

    $page->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-testid="schedule-section-general"] h3', '15:00 - 16:10')
        ->assertSeeIn('[data-testid="schedule-section-general"] [data-testid="schedule-desktop-table"]', 'Course A')
        ->assertSeeIn('[data-testid="schedule-section-general"] [data-testid="schedule-desktop-table"]', 'Course B')
        ->assertDontSeeIn('[data-testid="schedule-section-general"]', 'Micro Credit Course')
        ->assertSeeIn('[data-testid="schedule-section-micro"] [data-testid="schedule-desktop-table"]', 'Micro Credit Course');
});

it('regroups courses by department when 學系 is selected, dropping the exam-time split', function () {
    $term = config('app.current_semester');

    Course::factory()->create([
        'name' => 'Networking Fundamentals',
        'term' => $term,
        'department' => '資訊工程學系',
        'final_date' => now()->next('Saturday'),
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:10',
    ]);

    $remoteCourse = Course::factory()->create([
        'name' => 'Remote Database Systems',
        'term' => $term,
        'department' => '資訊工程學系',
    ]);
    CourseClass::factory()->create([
        'course_id' => $remoteCourse->id,
        'type' => CourseClassType::FullRemote,
    ]);

    $page = visit(route('course.schedule'));

    $page->select('[data-testid="group-by-select"]', 'department')
        ->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-testid="schedule-section-department"] h3', '資訊工程學系')
        ->assertSeeIn('[data-testid="schedule-section-department"] [data-testid="schedule-desktop-table"]', 'Networking Fundamentals')
        ->assertSeeIn('[data-testid="schedule-section-department"] [data-testid="schedule-desktop-table"]', 'Remote Database Systems')
        ->assertMissing('[data-testid="schedule-section-general"]')
        ->assertMissing('[data-testid="schedule-section-micro"]');
});

it('regroups courses by credits when 學分數 is selected', function () {
    $term = config('app.current_semester');

    Course::factory()->create([
        'name' => 'Three Credit Course',
        'term' => $term,
        'credits' => 3,
        'final_date' => now()->next('Saturday'),
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:10',
    ]);
    Course::factory()->create([
        'name' => 'Two Credit Course',
        'term' => $term,
        'credits' => 2,
        'final_date' => now()->next('Saturday'),
        'exam_time_start' => '13:00',
        'exam_time_end' => '14:10',
    ]);

    $page = visit(route('course.schedule'));

    $page->select('[data-testid="group-by-select"]', 'credits')
        ->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-testid="schedule-section-credits"] [data-testid="schedule-desktop-table"]', 'Three Credit Course')
        ->assertSeeIn('[data-testid="schedule-section-credits"] [data-testid="schedule-desktop-table"]', 'Two Credit Course');
});

it('lists the courses of an exam slot by department, then by name, with courses without a department last', function () {
    $term = config('app.current_semester');
    $finalDate = now()->next('Saturday');

    foreach ([
        ['Alpha No Department', null],
        ['Zeta Business', '商學系'],
        ['Beta Humanities', '人文學系'],
        ['Alpha Business', '商學系'],
    ] as [$name, $department]) {
        Course::factory()->create([
            'name' => $name,
            'term' => $term,
            'department' => $department,
            'final_date' => $finalDate,
            'exam_time_start' => '15:00',
            'exam_time_end' => '16:10',
        ]);
    }

    $page = visit(route('course.schedule'))
        ->assertSee('Zeta Business');

    $names = json_decode($page->script(<<<'JS'
        JSON.stringify([...document.querySelectorAll('[data-testid="schedule-section-general"] [data-testid="schedule-desktop-table"] tbody th')].map(th => th.textContent.trim()))
    JS), true);

    expect($names)->toBe(['Beta Humanities', 'Alpha Business', 'Zeta Business', 'Alpha No Department']);
});
