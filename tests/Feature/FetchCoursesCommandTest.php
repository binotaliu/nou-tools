<?php

use App\Enums\CourseClassType;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Facades\Http;

it('fails with invalid term format', function () {
    $this->artisan('course:fetch', ['term' => 'invalid'])
        ->assertFailed();
});

it('fails with missing semester letter', function () {
    $this->artisan('course:fetch', ['term' => '2025'])
        ->assertFailed();
});

it('fetches and stores courses from HTML pages', function () {
    $vc1Html = file_get_contents(__DIR__.'/../fixtures/vc1_sample.html');
    $vc3Html = file_get_contents(__DIR__.'/../fixtures/vc3_sample.html');
    $vc4Html = file_get_contents(__DIR__.'/../fixtures/vc4_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response($vc1Html, 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response($vc3Html, 200),
        'vc.nou.edu.tw/vc4/*' => Http::response($vc4Html, 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])
        ->assertSuccessful();

    expect(Course::query()->count())->toBeGreaterThan(0);
    expect(CourseClass::query()->count())->toBeGreaterThan(0);
    expect(ClassSchedule::query()->count())->toBeGreaterThan(0);

    $course = Course::query()->where('name', '做伙唱歌學台語')->first();
    expect($course)->not->toBeNull();
    expect($course->term)->toBe('2025B');

    $morningClasses = $course->classes()->where('type', CourseClassType::Morning)->get();
    expect($morningClasses)->toHaveCount(1);
    expect($morningClasses->first()->code)->toBe('ZZZ201');
    expect($morningClasses->first()->teacher_name)->toBe('蔡惠名老師');

    $eveningClasses = $course->classes()->where('type', CourseClassType::Evening)->get();
    expect($eveningClasses)->toHaveCount(3);
});

it('creates schedules with correct dates for B semester', function () {
    $vc1Html = file_get_contents(__DIR__.'/../fixtures/vc1_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response($vc1Html, 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc4/*' => Http::response('<html><body></body></html>', 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])
        ->assertSuccessful();

    $courseClass = CourseClass::query()->first();
    $schedules = $courseClass->schedules()->orderBy('date')->get();

    expect($schedules)->toHaveCount(4);
    expect($schedules[0]->date->format('Y-m-d'))->toBe('2026-03-09');
    expect($schedules[1]->date->format('Y-m-d'))->toBe('2026-03-30');
    expect($schedules[2]->date->format('Y-m-d'))->toBe('2026-05-11');
    expect($schedules[3]->date->format('Y-m-d'))->toBe('2026-06-08');
});

it('creates schedules with correct dates for A semester', function () {
    $vc1Html = file_get_contents(__DIR__.'/../fixtures/vc1_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response($vc1Html, 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc4/*' => Http::response('<html><body></body></html>', 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025A'])
        ->assertSuccessful();

    $courseClass = CourseClass::query()->first();
    $schedules = $courseClass->schedules()->orderBy('date')->get();

    expect($schedules)->toHaveCount(4);
    expect($schedules[0]->date->format('Y'))->toBe('2025');
});

it('does not duplicate courses on re-run', function () {
    $vc1Html = file_get_contents(__DIR__.'/../fixtures/vc1_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response($vc1Html, 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc4/*' => Http::response('<html><body></body></html>', 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])->assertSuccessful();
    $firstCount = Course::query()->count();

    $this->artisan('course:fetch', ['term' => '2025B'])->assertSuccessful();
    $secondCount = Course::query()->count();

    expect($secondCount)->toBe($firstCount);
});

it('does not duplicate course classes on re-run and preserves IDs', function () {
    $vc1Html = file_get_contents(__DIR__.'/../fixtures/vc1_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response($vc1Html, 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc4/*' => Http::response('<html><body></body></html>', 200),
    ]);

    // First run
    $this->artisan('course:fetch', ['term' => '2025B'])->assertSuccessful();
    $firstClassCount = CourseClass::query()->count();
    $firstScheduleCount = ClassSchedule::query()->count();

    $firstClassIds = CourseClass::query()->orderBy('id')->pluck('id')->toArray();
    $firstScheduleIds = ClassSchedule::query()->orderBy('id')->pluck('id')->toArray();

    // Second run should not create new records
    $this->artisan('course:fetch', ['term' => '2025B'])->assertSuccessful();
    $secondClassCount = CourseClass::query()->count();
    $secondScheduleCount = ClassSchedule::query()->count();

    expect($secondClassCount)->toBe($firstClassCount);
    expect($secondScheduleCount)->toBe($firstScheduleCount);

    $secondClassIds = CourseClass::query()->orderBy('id')->pluck('id')->toArray();
    $secondScheduleIds = ClassSchedule::query()->orderBy('id')->pluck('id')->toArray();

    expect($secondClassIds)->toBe($firstClassIds);
    expect($secondScheduleIds)->toBe($firstScheduleIds);
});

it('handles HTTP failures gracefully', function () {
    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response('', 500),
        'vc.nou.edu.tw/vc2/*' => Http::response('', 500),
        'vc.nou.edu.tw/vc3/*' => Http::response('', 500),
        'vc.nou.edu.tw/vc4/*' => Http::response('', 500),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])
        ->assertSuccessful();

    expect(Course::query()->count())->toBe(0);
});

it('stores full remote courses with custom time slots', function () {
    $vc4Html = file_get_contents(__DIR__.'/../fixtures/vc4_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc4/*' => Http::response($vc4Html, 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])
        ->assertSuccessful();

    $familyCourse = Course::query()->where('name', '家族史與數位人文實作')->first();
    expect($familyCourse)->not->toBeNull();

    $class = $familyCourse->classes()->first();
    expect($class->type)->toBe(CourseClassType::FullRemote);
    expect($class->start_time)->toBe('18:30');
    expect($class->end_time)->toBe('21:10');

    $schedules = $class->schedules;
    expect($schedules)->toHaveCount(6);
});

it('stores micro-credit courses separately from full remote', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_micro_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc4/*' => Http::response($html, 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])
        ->assertSuccessful();

    $fullRemoteClasses = CourseClass::query()->where('type', CourseClassType::FullRemote)->count();
    $microCreditClasses = CourseClass::query()->where('type', CourseClassType::MicroCredit)->count();

    expect($fullRemoteClasses)->toBe(1);
    expect($microCreditClasses)->toBeGreaterThan(0);
});

it('stores schedule time overrides for courses with irregular times', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_micro_sample.html');

    Http::fake([
        'vc.nou.edu.tw/vc1/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc2/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc3/*' => Http::response('<html><body></body></html>', 200),
        'vc.nou.edu.tw/vc4/*' => Http::response($html, 200),
    ]);

    $this->artisan('course:fetch', ['term' => '2025B'])
        ->assertSuccessful();

    $germanLaw = Course::query()->where('name', '法學德文（三）')->first();
    expect($germanLaw)->not->toBeNull();

    $class = $germanLaw->classes()->first();
    expect($class->type)->toBe(CourseClassType::MicroCredit);
    expect($class->start_time)->toBe('09:10');
    expect($class->end_time)->toBe('12:00');

    $schedules = $class->schedules()->orderBy('date')->get();
    expect($schedules)->toHaveCount(5);

    // Sessions 1 and 2 (02/24, 03/03) should have time overrides
    expect($schedules[0]->date->format('Y-m-d'))->toBe('2026-02-24');
    expect($schedules[0]->start_time)->toBe('18:30');
    expect($schedules[0]->end_time)->toBe('21:00');

    expect($schedules[1]->date->format('Y-m-d'))->toBe('2026-03-03');
    expect($schedules[1]->start_time)->toBe('18:30');
    expect($schedules[1]->end_time)->toBe('21:00');

    // Sessions 3, 4, 5 should have no time overrides (use class defaults)
    expect($schedules[2]->start_time)->toBeNull();
    expect($schedules[2]->end_time)->toBeNull();
    expect($schedules[3]->start_time)->toBeNull();
    expect($schedules[3]->end_time)->toBeNull();
    expect($schedules[4]->start_time)->toBeNull();
    expect($schedules[4]->end_time)->toBeNull();
});
