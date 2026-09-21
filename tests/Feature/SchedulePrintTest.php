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

it('gives each course one exam row with its dates under the weekday they fall on', function () {
    // 2026-04-25 is a Saturday, 2026-06-28 a Sunday; both exams share the time slot.
    $schedule = printableSchedule([
        'name' => '會計學',
        'midterm_date' => '2026-04-25',
        'final_date' => '2026-06-28',
        'exam_time_start' => '09:00',
        'exam_time_end' => '10:30',
    ]);

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025B');

    expect($page->exams)->toHaveCount(1);

    $row = $page->exams[0];
    expect($row->courseName)->toBe('會計學')
        ->and($row->time)->toBe('09:00 - 10:30')
        ->and($row->saturday)->toBe([['kind' => '期中考', 'label' => '4/25']])
        ->and($row->sunday)->toBe([['kind' => '期末考', 'label' => '6/28']])
        ->and($row->other)->toBe([]);
});

it('lists both exams in one column when they fall on the same weekday', function () {
    // 2026-04-25 and 2026-06-27 are both Saturdays.
    $schedule = printableSchedule(['midterm_date' => '2026-04-25', 'final_date' => '2026-06-27']);

    $row = app(BuildSchedulePrintPage::class)($schedule, '2025B')->exams[0];

    expect(array_column($row->saturday, 'label'))->toBe(['4/25', '6/27'])
        ->and($row->sunday)->toBe([]);
});

it('orders rows by earliest exam and leaves out courses without a date', function () {
    $schedule = printableSchedule(['name' => '期末在後', 'final_date' => '2026-06-28']);
    foreach ([['name' => '未公布'], ['name' => '期中在前', 'midterm_date' => '2026-04-25']] as $attributes) {
        $course = Course::factory()->create(array_merge(['term' => '2025B'], $attributes));
        StudentScheduleItem::create([
            'student_schedule_id' => $schedule->id,
            'course_id' => $course->id,
            'course_class_id' => CourseClass::factory()->create(['course_id' => $course->id])->id,
        ]);
    }

    $page = app(BuildSchedulePrintPage::class)($schedule->refresh(), '2025B');

    expect(array_map(fn ($row) => $row->courseName, $page->exams))->toBe(['期中在前', '期末在後']);
});

it('keeps weekday exams visible', function () {
    // 2026-04-27 is a Monday.
    $schedule = printableSchedule(['name' => '統計學', 'midterm_date' => '2026-04-27']);

    $row = app(BuildSchedulePrintPage::class)($schedule, '2025B')->exams[0];

    expect($row->saturday)->toBe([])
        ->and($row->sunday)->toBe([])
        ->and($row->other)->toHaveCount(1)
        ->and($row->other[0]['kind'])->toBe('期中考');
});

it('omits the midterm for summer terms', function () {
    $schedule = printableSchedule([
        'midterm_date' => '2026-07-25',
        'final_date' => '2026-08-23',
    ], '2025C');

    $row = app(BuildSchedulePrintPage::class)($schedule, '2025C')->exams[0];

    expect($row->saturday)->toBe([])
        ->and($row->sunday)->toBe([['kind' => '期末考', 'label' => '8/23']]);
});

it('hides courses without an exam date from the exam table but not the course list', function () {
    $schedule = printableSchedule(['name' => '哲學']);

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025B');

    expect($page->exams)->toBe([])
        ->and($page->courses[0]->name)->toBe('哲學');
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
    expect($march->classDays)->toHaveCount(1)
        ->and($march->title)->toContain('3 月')
        ->and($march->weeks[0])->toHaveCount(7)
        ->and(array_filter(array_slice($march->weeks[0], 0, 6)))->toBe([])
        ->and($march->weeks[0][6])->toBe(['day' => 1, 'hasClass' => false, 'isExam' => false])
        ->and($march->weeks[1][0])->toBe(['day' => 2, 'hasClass' => false, 'isExam' => false])
        ->and($march->weeks[1][6])->toBe(['day' => 8, 'hasClass' => true, 'isExam' => false]);

    $april = $page->months[1];
    $daysWithClass = collect($april->weeks)->flatten(1)->filter()->where('hasClass', true);
    expect($april->title)->toContain('4 月')->and($daysWithClass)->toBeEmpty()->and($april->classDays)->toBe([]);

    $may = $page->months[2];
    expect(collect($may->weeks)->flatten(1)->filter()->where('hasClass', true)->pluck('day')->all())->toBe([2])
        ->and(collect($may->weeks)->flatten(1)->filter()->count())->toBe(31);
});

it('orders the courses on one class date by start time, not alphabetically', function () {
    $schedule = printableSchedule(['name' => '下午班']);
    $afternoon = $schedule->items->first()->courseClass;
    $afternoon->update(['start_time' => '14:00', 'end_time' => '15:50']);

    foreach ([['name' => '晚間班', 'start' => '19:00'], ['name' => '早上班', 'start' => '9:00']] as $extra) {
        $course = Course::factory()->create(['name' => $extra['name'], 'term' => '2025B']);
        $class = CourseClass::factory()->create(['course_id' => $course->id, 'start_time' => $extra['start'], 'end_time' => '23:00']);
        StudentScheduleItem::create([
            'student_schedule_id' => $schedule->id,
            'course_id' => $course->id,
            'course_class_id' => $class->id,
        ]);
        ClassSchedule::factory()->create(['class_id' => $class->id, 'date' => '2026-09-14']);
    }

    ClassSchedule::factory()->create(['class_id' => $afternoon->id, 'date' => '2026-09-14']);

    $page = app(BuildSchedulePrintPage::class)($schedule->refresh(), '2025B');

    // "9:00" sorts after "14:00" and "19:00" as a string, so this fails on strcmp.
    expect(collect($page->months[0]->classDays[0]['courses'])->pluck('time')->all())
        ->toBe(['9:00', '14:00', '19:00']);
});

it('pads every month to the same number of week rows', function () {
    $schedule = printableSchedule();
    $class = $schedule->items->first()->courseClass;

    // March 2026 needs six week rows, April and May five.
    foreach (['2026-03-08', '2026-05-02'] as $date) {
        ClassSchedule::factory()->create(['class_id' => $class->id, 'date' => $date]);
    }

    $html = $this->get(route('schedules.print', ['schedule' => $schedule->refresh(), 'term' => '2025B']))
        ->assertOk()
        ->getContent();

    // Three months, each six rows of seven day cells.
    expect(substr_count($html, 'h-[5mm]'))->toBe(3 * 6 * 7);
});

it('lists the courses held on each class date under its month', function () {
    $schedule = printableSchedule(['name' => '心理學']);
    $morning = $schedule->items->first()->courseClass;
    $morning->update(['start_time' => '09:00', 'end_time' => '10:50']);

    $other = Course::factory()->create(['name' => '婦女健康', 'term' => '2025B']);
    $evening = CourseClass::factory()->create(['course_id' => $other->id, 'start_time' => '19:00', 'end_time' => '20:50']);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $other->id,
        'course_class_id' => $evening->id,
    ]);

    // 2026-09-14 is a Monday: both courses meet; 2026-09-16 only the evening class.
    ClassSchedule::factory()->create(['class_id' => $evening->id, 'date' => '2026-09-14']);
    ClassSchedule::factory()->create(['class_id' => $morning->id, 'date' => '2026-09-14']);
    ClassSchedule::factory()->create(['class_id' => $evening->id, 'date' => '2026-09-16']);

    $page = app(BuildSchedulePrintPage::class)($schedule->refresh(), '2025B');

    expect($page->months)->toHaveCount(1)
        ->and($page->months[0]->classDays)->toBe([
            ['label' => '9/14 (一)', 'courses' => [
                ['name' => '心理學', 'time' => '09:00'],
                ['name' => '婦女健康', 'time' => '19:00'],
            ]],
            ['label' => '9/16 (三)', 'courses' => [
                ['name' => '婦女健康', 'time' => '19:00'],
            ]],
        ]);
});

it('marks exam dates and extends the calendars to the final exam month', function () {
    // 2026-03-08 is the only class; the midterm (2026-04-25, Saturday) and the
    // final (2026-05-30, Saturday) fall in later months.
    $schedule = printableSchedule(['name' => '會計學', 'midterm_date' => '2026-04-25', 'final_date' => '2026-05-30']);
    ClassSchedule::factory()->create(['class_id' => $schedule->items->first()->course_class_id, 'date' => '2026-03-08']);

    $page = app(BuildSchedulePrintPage::class)($schedule->refresh(), '2025B');

    expect($page->months)->toHaveCount(3);

    [$march, $april, $may] = $page->months;
    $cell = fn ($month, int $day) => collect($month->weeks)->flatten(1)->filter()->firstWhere('day', $day);

    expect($cell($march, 8))->toBe(['day' => 8, 'hasClass' => true, 'isExam' => false])
        ->and($cell($april, 25))->toBe(['day' => 25, 'hasClass' => false, 'isExam' => true])
        ->and($cell($may, 30))->toBe(['day' => 30, 'hasClass' => false, 'isExam' => true])
        ->and($march->examDays)->toBe([])
        ->and($april->examDays)->toBe([['label' => '4/25 (六)', 'kind' => '期中考', 'courses' => ['會計學']]])
        ->and($may->examDays)->toBe([['label' => '5/30 (六)', 'kind' => '期末考', 'courses' => ['會計學']]]);
});

it('marks a date that has both a class and an exam', function () {
    $schedule = printableSchedule(['midterm_date' => '2026-03-08']);
    ClassSchedule::factory()->create(['class_id' => $schedule->items->first()->course_class_id, 'date' => '2026-03-08']);

    $march = app(BuildSchedulePrintPage::class)($schedule->refresh(), '2025B')->months[0];

    expect(collect($march->weeks)->flatten(1)->filter()->firstWhere('day', 8))
        ->toBe(['day' => 8, 'hasClass' => true, 'isExam' => true]);
});

it('draws calendars for exams even when no class dates exist', function () {
    $schedule = printableSchedule(['final_date' => '2026-06-28']);

    $page = app(BuildSchedulePrintPage::class)($schedule, '2025B');

    expect($page->months)->toHaveCount(1)
        ->and($page->months[0]->title)->toContain('6 月')
        ->and($page->months[0]->classDays)->toBe([]);
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
        ->assertSee('週日')
        ->assertSee('期中')
        ->assertSee('期末')
        ->assertSee('4/25')
        ->assertSee('考試班級')
        ->assertSee('期中教室')
        ->assertSee('期末教室')
        ->assertSee('<svg', false)
        ->assertSee(route('schedules.show', $schedule))
        ->assertSee('noindex', false);
});

it('only asks for the final exam classroom in summer terms', function () {
    $schedule = printableSchedule(['final_date' => '2026-08-23'], '2025C');

    $this->get(route('schedules.print', ['schedule' => $schedule, 'term' => '2025C']))
        ->assertOk()
        ->assertSee('期末教室')
        ->assertDontSee('期中教室');
});

it('shows the schedule name and semester on both halves of the sheet', function () {
    $schedule = printableSchedule();

    $html = $this->get(route('schedules.print', ['schedule' => $schedule, 'term' => '2025B']))->assertOk()->getContent();

    expect(substr_count($html, '列印課表'))->toBeGreaterThanOrEqual(3) // <title> plus each half's heading
        ->and(substr_count($html, '114 學年度下學期'))->toBeGreaterThanOrEqual(3);
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
