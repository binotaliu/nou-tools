<?php

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Facades\File;
use NouTools\Domains\Schedules\Actions\ImportCourseSelectSimulation;

function fakeCourseSelectSimJson(): string
{
    return json_encode([
        '2026A' => [
            'calendar' => ['2026-09'],
            'courses' => [
                '1' => '無痛學AI',
                '2' => '公共生活倫理（二）：理論與個案',
                '3' => '某個不存在的課程',
            ],
            'videoSessions' => [
                '1' => [
                    '上午班' => [
                        'dates' => ['2026-09-08', '2026-10-06'],
                        'start' => '9:00',
                        'end' => '10:50',
                    ],
                    '夜間班' => [
                        'dates' => ['2026-09-08', '2026-10-06'],
                        'start' => '19:00',
                        'end' => '20:50',
                    ],
                ],
                '2' => [
                    '未知時段' => [
                        'dates' => ['2026-09-08'],
                        'start' => '13:00',
                        'end' => '14:50',
                    ],
                    '上午班' => [
                        'dates' => ['2026-09-08'],
                        'start' => '9:00',
                        'end' => '10:50',
                    ],
                ],
                '3' => [
                    '下午班' => [
                        'dates' => ['2026-09-08'],
                        'start' => '14:00',
                        'end' => '15:50',
                    ],
                ],
            ],
        ],
    ]);
}

beforeEach(function () {
    File::shouldReceive('exists')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturnTrue()
        ->byDefault();

    File::shouldReceive('get')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturn(fakeCourseSelectSimJson())
        ->byDefault();
});

it('creates tentative classes and schedules matched by exact name', function () {
    $course = Course::factory()->create(['name' => '無痛學AI', 'term' => '2026A']);

    $result = (new ImportCourseSelectSimulation)('2026A');

    expect($result['classes'])->toBe(2);

    $morning = CourseClass::query()->where('course_id', $course->id)->where('type', CourseClassType::Morning->value)->first();
    $evening = CourseClass::query()->where('course_id', $course->id)->where('type', CourseClassType::Evening->value)->first();

    expect($morning)->not->toBeNull();
    expect($morning->is_tentative)->toBeTrue();
    expect($morning->start_time)->toBe('9:00');
    expect($morning->end_time)->toBe('10:50');
    expect($morning->teacher_name)->toBe('');
    expect($morning->link)->toBe('');
    expect($morning->schedules()->pluck('date')->map->format('Y-m-d')->all())
        ->toEqualCanonicalizing(['2026-09-08', '2026-10-06']);

    expect($evening)->not->toBeNull();
});

it('matches courses whose DB name still has punctuation via normalization', function () {
    $course = Course::factory()->create(['name' => '公共生活倫理（二）：理論與個案', 'term' => '2026A']);

    (new ImportCourseSelectSimulation)('2026A');

    expect(CourseClass::query()->where('course_id', $course->id)->where('is_tentative', true)->count())->toBe(1);
});

it('skips simulated courses with no matching Course record', function () {
    Course::factory()->create(['name' => '無痛學AI', 'term' => '2026A']);

    (new ImportCourseSelectSimulation)('2026A');

    $unmatched = Course::query()->where('name', '某個不存在的課程')->first();

    expect($unmatched)->toBeNull();
    expect(CourseClass::query()->where('is_tentative', true)->count())->toBe(2);
});

it('skips session labels that do not map to a known class type', function () {
    $course = Course::factory()->create(['name' => '公共生活倫理二理論與個案', 'term' => '2026A']);

    (new ImportCourseSelectSimulation)('2026A');

    expect(CourseClass::query()->where('course_id', $course->id)->where('is_tentative', true)->count())->toBe(1);
    expect(CourseClass::query()->where('course_id', $course->id)->where('type', CourseClassType::Morning->value)->exists())->toBeTrue();
});

it('does nothing when the file is missing', function () {
    File::shouldReceive('exists')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturnFalse();

    Course::factory()->create(['name' => '無痛學AI', 'term' => '2026A']);

    $result = (new ImportCourseSelectSimulation)('2026A');

    expect($result)->toBe(['terms' => 0, 'classes' => 0]);
    expect(CourseClass::query()->where('is_tentative', true)->count())->toBe(0);
});

it('does nothing when the term is not present', function () {
    $result = (new ImportCourseSelectSimulation)('2099Z');

    expect($result['classes'])->toBe(0);
});

it('imports every term in the file when no term is given', function () {
    Course::factory()->create(['name' => '無痛學AI', 'term' => '2026A']);

    $result = (new ImportCourseSelectSimulation)();

    expect($result['terms'])->toBe(1);
    expect($result['classes'])->toBe(2);
});

it('is idempotent: re-importing updates existing tentative classes and drops stale dates', function () {
    $course = Course::factory()->create(['name' => '無痛學AI', 'term' => '2026A']);

    (new ImportCourseSelectSimulation)('2026A');

    expect(CourseClass::query()->where('is_tentative', true)->count())->toBe(2);

    $updatedJson = json_encode([
        '2026A' => [
            'courses' => ['1' => '無痛學AI'],
            'videoSessions' => [
                '1' => [
                    '上午班' => [
                        'dates' => ['2026-10-06', '2026-11-03'],
                        'start' => '9:00',
                        'end' => '10:50',
                    ],
                ],
            ],
        ],
    ]);

    File::shouldReceive('get')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturn($updatedJson);

    (new ImportCourseSelectSimulation)('2026A');

    // The evening class is no longer in the source data, but re-importing
    // does not delete classes outright — only re-syncs the ones present.
    expect(CourseClass::query()->where('course_id', $course->id)->where('is_tentative', true)->count())->toBe(2);

    $morning = CourseClass::query()->where('course_id', $course->id)->where('type', CourseClassType::Morning->value)->first();

    expect($morning->schedules()->pluck('date')->map->format('Y-m-d')->all())
        ->toEqualCanonicalizing(['2026-10-06', '2026-11-03']);
});
