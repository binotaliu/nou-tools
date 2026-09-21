<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;
use NouTools\Domains\Schedules\Actions\BuildSchedulePrintPage;
use NouTools\Domains\Shared\Pdf\HtmlToPdf;

function printableSchedule(array $courseAttributes = [], string $term = '2025B'): StudentSchedule
{
    $course = Course::factory()->create(array_merge(['term' => $term, 'credits' => 3], $courseAttributes));
    $courseClass = CourseClass::factory()->create(['course_id' => $course->id]);

    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '列印課表']);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
        'course_class_id' => $courseClass->id,
    ]);

    return $schedule;
}

it('lists courses with their credits', function () {
    $schedule = printableSchedule(['name' => '經濟學', 'credits' => 4]);

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025B');

    expect($page->name)->toBe('列印課表')
        ->and($page->courses)->toHaveCount(1)
        ->and($page->courses[0]->name)->toBe('經濟學')
        ->and($page->courses[0]->credits)->toBe(4)
        ->and($page->shareUrl)->toBe(route('schedules.show', $schedule))
        ->and($page->qrCodeSvg)->toContain('<svg');
});

it('places exams on the Saturday and Sunday columns', function () {
    // 2026-04-25 is a Saturday, 2026-06-28 a Sunday.
    $schedule = printableSchedule([
        'name' => '會計學',
        'midterm_date' => '2026-04-25',
        'final_date' => '2026-06-28',
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:30',
    ]);

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025B');

    expect($page->saturdayExams)->toHaveCount(1)
        ->and($page->saturdayExams[0]->entries)->toBe([
            ['kind' => '期中考', 'time' => '09:00 - 10:30', 'courseName' => '會計學'],
        ])
        ->and($page->sundayExams)->toHaveCount(1)
        ->and($page->sundayExams[0]->entries[0]['kind'])->toBe('期末考')
        ->and($page->otherExams)->toBe([])
        ->and($page->undatedCourseNames)->toBe([]);
});

it('groups every exam on the same date under one day', function () {
    $first = printableSchedule(['name' => '國文', 'final_date' => '2026-06-28', 'exam_time_start' => '13:30', 'exam_time_end' => '14:40']);
    $second = Course::factory()->create(['name' => '英文', 'term' => '2025B', 'final_date' => '2026-06-28', 'exam_time_start' => '08:30', 'exam_time_end' => '09:40']);
    $class = CourseClass::factory()->create(['course_id' => $second->id]);
    StudentScheduleItem::create([
        'student_schedule_id' => $first->id,
        'course_id' => $second->id,
        'course_class_id' => $class->id,
    ]);

    $page = app(BuildSchedulePrintPage::class)($first->refresh(), '2025B');

    expect($page->sundayExams)->toHaveCount(1)
        ->and(array_column($page->sundayExams[0]->entries, 'courseName'))->toBe(['英文', '國文']);
});

it('keeps weekday exams visible in their own bucket', function () {
    // 2026-04-27 is a Monday.
    $schedule = printableSchedule(['name' => '統計學', 'midterm_date' => '2026-04-27']);

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025B');

    expect($page->saturdayExams)->toBe([])
        ->and($page->sundayExams)->toBe([])
        ->and($page->otherExams)->toHaveCount(1)
        ->and($page->otherExams[0]->entries[0]['courseName'])->toBe('統計學');
});

it('omits the midterm for summer terms', function () {
    $schedule = printableSchedule([
        'midterm_date' => '2026-07-25',
        'final_date' => '2026-08-23',
    ], '2025C');

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025C');

    expect($page->saturdayExams)->toBe([])
        ->and($page->sundayExams)->toHaveCount(1)
        ->and($page->sundayExams[0]->entries[0]['kind'])->toBe('期末考');
});

it('lists courses without an exam date as undated', function () {
    $schedule = printableSchedule(['name' => '哲學']);

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025B');

    expect($page->undatedCourseNames)->toBe(['哲學'])
        ->and($page->saturdayExams)->toBe([]);
});

it('builds monthly calendars with gap months and Monday-first weeks', function () {
    $schedule = printableSchedule();
    $class = $schedule->items->first()->courseClass;

    // March 2026 starts on a Sunday; April has no classes; May 2026 starts on a Friday.
    foreach (['2026-03-08', '2026-05-02'] as $date) {
        ClassSchedule::factory()->create(['class_id' => $class->id, 'date' => $date]);
    }

    $page = app(BuildSchedulePrintPage::class)($schedule->refresh(), '2025B');

    expect($page->months)->toHaveCount(3);

    $march = $page->months[0];
    // Sunday the 1st sits in the last column of the first week.
    expect($march->title)->toContain('3 月')
        ->and($march->weeks[0])->toHaveCount(7)
        ->and(array_filter(array_slice($march->weeks[0], 0, 6)))->toBe([])
        ->and($march->weeks[0][6])->toBe(['day' => 1, 'hasClass' => false])
        ->and($march->weeks[1][0])->toBe(['day' => 2, 'hasClass' => false])
        ->and($march->weeks[1][6])->toBe(['day' => 8, 'hasClass' => true]);

    $april = $page->months[1];
    $daysWithClass = collect($april->weeks)->flatten(1)->filter()->where('hasClass', true);
    expect($april->title)->toContain('4 月')->and($daysWithClass)->toBeEmpty();

    $may = $page->months[2];
    expect(collect($may->weeks)->flatten(1)->filter()->where('hasClass', true)->pluck('day')->all())->toBe([2])
        ->and(collect($may->weeks)->flatten(1)->filter()->count())->toBe(31);
});

it('has no calendars when the schedule has no classes', function () {
    $page = app(BuildSchedulePrintPage::class)(printableSchedule(), '2025B');

    expect($page->months)->toBe([]);
});

it('renders the printable sheet', function () {
    $schedule = printableSchedule([
        'name' => '會計學',
        'credits' => 3,
        'midterm_date' => '2026-04-25',
        'final_date' => '2026-06-28',
    ]);
    ClassSchedule::factory()->create([
        'class_id' => $schedule->items->first()->course_class_id,
        'date' => '2026-03-08',
    ]);

    $this->get(route('schedules.print', ['schedule' => $schedule, 'term' => '2025B']))
        ->assertOk()
        ->assertSee('列印課表')
        ->assertSee('114 學年度下學期')
        ->assertSee('會計學')
        ->assertSee('3 學分')
        ->assertSee('週六')
        ->assertSee('期中考')
        ->assertSee('期末考')
        ->assertSee('班級代碼')
        ->assertSee('<svg', false)
        ->assertSee(route('schedules.show', $schedule))
        ->assertSee('noindex', false);
});

it('renders an empty schedule without errors', function () {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '空課表']);

    $this->get(route('schedules.print', $schedule))
        ->assertOk()
        ->assertSee('此學期尚無課程');
});

it('returns 404 for an unknown schedule', function () {
    $this->get(route('schedules.print', ['schedule' => (string) Str::uuid()]))->assertNotFound();
});

/**
 * Stands in for Chromium so tests never launch a browser.
 */
function fakeHtmlToPdf(): object
{
    $fake = new class implements HtmlToPdf
    {
        /** @var array<int, string> */
        public array $rendered = [];

        public function landscapeA4(string $html): string
        {
            $this->rendered[] = $html;

            return "%PDF-fake\x00\xff";
        }
    };

    app()->instance(HtmlToPdf::class, $fake);

    return $fake;
}

it('serves the sheet as a PDF', function () {
    $fake = fakeHtmlToPdf();
    $schedule = printableSchedule(['name' => '會計學']);

    $response = $this->get(route('schedules.print.pdf', ['schedule' => $schedule, 'term' => '2025B']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Robots-Tag', 'noindex');
    expect($response->getContent())->toBe("%PDF-fake\x00\xff")
        ->and($fake->rendered)->toHaveCount(1)
        // Inlined, so the PDF renderer needs no origin to fetch assets from.
        ->and($fake->rendered[0])->toContain('會計學')->toContain('<style>')->not->toContain('nonce=');
});

it('reuses the rendered PDF while the sheet is unchanged', function () {
    $fake = fakeHtmlToPdf();
    $schedule = printableSchedule();

    $url = route('schedules.print.pdf', ['schedule' => $schedule, 'term' => '2025B']);

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();

    expect($fake->rendered)->toHaveCount(1);

    ClassSchedule::factory()->create(['class_id' => $schedule->items->first()->course_class_id, 'date' => '2026-03-08']);
    $this->get($url)->assertOk();

    expect($fake->rendered)->toHaveCount(2);
});

it('throttles PDF generation', function () {
    fakeHtmlToPdf();
    $schedule = printableSchedule();

    foreach (range(1, 6) as $ignored) {
        $this->get(route('schedules.print.pdf', $schedule))->assertOk();
    }

    $this->get(route('schedules.print.pdf', $schedule))->assertStatus(429);
});
