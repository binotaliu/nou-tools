<?php

use App\Enums\CourseClassType;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Program;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Courses\Actions\ImportProgramCourses;
use NouTools\Domains\Courses\Actions\ParseNouProgramCourses;

function sampleProgramsByRegion(): array
{
    $html = file_get_contents(__DIR__.'/../../Fixtures/svc_sample.html');

    return ['tc' => (new ParseNouProgramCourses)($html)];
}

it('stores programs, courses, classes and class dates', function () {
    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());

    expect(Program::query()->count())->toBe(2)
        ->and(Program::query()->orderBy('position')->pluck('region')->all())->toBe(['tc', 'tc']);

    $course = Course::query()->where('name', '測試課程乙')->firstOrFail();
    $class = $course->classes()->firstOrFail();

    expect($course->term)->toBe('2026A')
        ->and($course->is_special_program_only)->toBeTrue()
        ->and($class->type)->toBe(CourseClassType::SpecialProgram)
        ->and($class->program->name)->toBe('測試01(115-1)甲專班-視訊')
        ->and($class->code)->toBe('測試01(115-1)甲專班-視訊')
        ->and($class->teacher_name)->toBe('測試教師乙老師')
        ->and($class->link)->toBe('https://example.test/webex/program-a');

    $second = $class->schedules()->orderBy('date')->get()[1];

    expect($class->schedules)->toHaveCount(4)
        ->and($second->start_time)->toBe('14:00');
});

it('rolls January dates of a fall term into the next year', function () {
    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());

    $class = Course::query()->where('name', '測試課程丙')->firstOrFail()->classes()->firstOrFail();

    expect($class->schedules()->orderBy('date')->pluck('date')->map->format('Y-m-d')->all())
        ->toBe(['2026-10-17', '2026-10-24', '2027-01-09']);
});

it('is idempotent', function () {
    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());
    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());

    expect(Program::query()->count())->toBe(2)
        ->and(Course::query()->count())->toBe(4)
        ->and(CourseClass::query()->count())->toBe(5)
        ->and(ClassSchedule::query()->count())->toBe(4 + 4 + 3 + 2 + 2);
});

it('shares an existing general course without flagging it', function () {
    $general = Course::factory()->create(['name' => '測試課程甲', 'term' => '2026A']);

    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());

    expect(Course::query()->where('name', '測試課程甲')->count())->toBe(1)
        ->and($general->fresh()->is_special_program_only)->toBeFalse()
        ->and($general->classes()->whereNotNull('program_id')->count())->toBe(2);
});

it('drops dates the page no longer lists', function () {
    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());

    $programs = sampleProgramsByRegion();
    $programs['tc'][0]['courses'][0]['dates'] = ['09/19'];

    app(ImportProgramCourses::class)('2026A', $programs);

    $class = Course::query()->where('name', '測試課程甲')->firstOrFail()
        ->classes()->where('program_id', Program::query()->orderBy('position')->value('id'))->firstOrFail();

    expect($class->schedules)->toHaveCount(1);
});

it('removes stale programs only when asked', function () {
    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());

    $fewer = sampleProgramsByRegion();
    array_pop($fewer['tc']);

    app(ImportProgramCourses::class)('2026A', $fewer);
    expect(Program::query()->count())->toBe(2);

    app(ImportProgramCourses::class)('2026A', $fewer, removeStale: true);

    expect(Program::query()->count())->toBe(1)
        ->and(Course::query()->where('name', '測試課程丁')->exists())->toBeFalse()
        ->and(Course::query()->where('name', '測試課程甲')->exists())->toBeTrue();
});

it('keeps stale classes that a schedule already uses', function () {
    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion());

    $class = Course::query()->where('name', '測試課程丁')->firstOrFail()->classes()->firstOrFail();
    $item = StudentScheduleItem::query()->create([
        'student_schedule_id' => StudentSchedule::factory()->create()->id,
        'course_id' => $class->course_id,
        'course_class_id' => $class->id,
    ]);

    $fewer = sampleProgramsByRegion();
    array_pop($fewer['tc']);

    app(ImportProgramCourses::class)('2026A', $fewer, removeStale: true);

    expect($item->fresh())->not->toBeNull()
        ->and(Program::query()->count())->toBe(2);
});

it('leaves other terms alone when removing stale data', function () {
    $other = Program::factory()->create(['term' => '2025B']);
    CourseClass::factory()->create(['program_id' => $other->id, 'type' => CourseClassType::SpecialProgram]);

    app(ImportProgramCourses::class)('2026A', sampleProgramsByRegion(), removeStale: true);

    expect(Program::query()->where('term', '2025B')->count())->toBe(1)
        ->and(CourseClass::query()->where('program_id', $other->id)->count())->toBe(1);
});

it('fetches every region through program:fetch', function () {
    $html = file_get_contents(__DIR__.'/../../Fixtures/svc_sample.html');

    Http::fake([
        'vc.nou.edu.tw/svc/tc.html' => Http::response($html, 200),
        'vc.nou.edu.tw/svc/*' => Http::response('<html><body></body></html>', 200),
    ]);

    $this->artisan('program:fetch', ['term' => '2026A'])->assertFailed();

    expect(Program::query()->count())->toBe(2);
});

it('succeeds and removes stale data when every region page has programs', function () {
    $html = file_get_contents(__DIR__.'/../../Fixtures/svc_sample.html');
    Http::fake(['vc.nou.edu.tw/svc/*' => Http::response($html, 200)]);

    Program::factory()->create(['term' => '2026A', 'name' => '已不存在的專班']);

    $this->artisan('program:fetch', ['term' => '2026A'])->assertSuccessful();

    expect(Program::query()->where('name', '已不存在的專班')->exists())->toBeFalse();
});

it('rejects an invalid term', function () {
    $this->artisan('program:fetch', ['term' => 'nope'])->assertFailed();
});

it('unflags a special-program-only course once course:fetch sees it', function () {
    $course = Course::factory()->create(['name' => '測試課程甲', 'term' => '2025B', 'is_special_program_only' => true]);

    $vc1Html = <<<'HTML'
    <html><body><div class="row">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="card-title">01.測試課程甲</h4>
                <h6 class="card-subtitle mb-2">時間：09:00~10:50</h6>
            </div>
            <div class="card-footer">
                <class_icon><a href="https://example.test/room"><img src="images/zzz101.png" alt="zzz101" /></a></class_icon>
                <h5><c1text>測試教師甲老師</c1text></h5>
                <h6><c3text>09/19、10/17</c3text></h6>
            </div>
        </div>
    </div></body></html>
    HTML;

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response($vc1Html, 200),
        'vc.nou.edu.tw/*' => Http::response('<html><body></body></html>', 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])->assertSuccessful();

    expect($course->fresh()->is_special_program_only)->toBeFalse()
        ->and($course->classes()->count())->toBe(1);
});
