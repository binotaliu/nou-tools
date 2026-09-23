<?php

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Facades\File;
use NouTools\Domains\Schedules\Actions\ImportCourseSelectSimulation;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorCourseClassViewModel;

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

    expect($result['classes'])->toBe(4);

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

it('creates a Course when no matching record exists', function () {
    Course::factory()->create(['name' => '無痛學AI', 'term' => '2026A']);

    (new ImportCourseSelectSimulation)('2026A');

    $created = Course::query()->where('name', '某個不存在的課程')->where('term', '2026A')->first();

    expect($created)->not->toBeNull();
    expect(CourseClass::query()->where('course_id', $created->id)->where('is_tentative', true)->count())->toBe(1);
    expect(CourseClass::query()->where('is_tentative', true)->count())->toBe(4);
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
    expect($result['classes'])->toBe(4);
});

it('is idempotent: re-importing updates existing tentative classes and drops stale dates', function () {
    $course = Course::factory()->create(['name' => '無痛學AI', 'term' => '2026A']);

    (new ImportCourseSelectSimulation)('2026A');

    expect(CourseClass::query()->where('is_tentative', true)->count())->toBe(4);

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

it('creates a placeholder full_remote/micro_credit class for exam-category courses with no video sessions', function () {
    $examJson = json_encode([
        '2026A' => [
            'courses' => [
                '1' => '遠距課程A',
                '2' => '微學分課程B',
            ],
            'videoSessions' => [],
            'exam' => [
                '全遠距' => ['1'],
                '微學分' => ['2'],
            ],
        ],
    ]);

    File::shouldReceive('get')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturn($examJson);

    $result = (new ImportCourseSelectSimulation)('2026A');

    expect($result['classes'])->toBe(2);

    $remoteCourse = Course::query()->where('name', '遠距課程A')->where('term', '2026A')->first();
    $microCourse = Course::query()->where('name', '微學分課程B')->where('term', '2026A')->first();

    expect($remoteCourse)->not->toBeNull();
    expect($microCourse)->not->toBeNull();

    $remoteClass = CourseClass::query()->where('course_id', $remoteCourse->id)->first();
    expect($remoteClass->type)->toBe(CourseClassType::FullRemote);
    expect($remoteClass->is_tentative)->toBeTrue();

    $microClass = CourseClass::query()->where('course_id', $microCourse->id)->first();
    expect($microClass->type)->toBe(CourseClassType::MicroCredit);
    expect($microClass->is_tentative)->toBeTrue();
});

it('retypes every existing video-session class to micro_credit when the course is listed under 微學分', function () {
    $examJson = json_encode([
        '2026A' => [
            'courses' => [
                '1' => '我的未來學習',
            ],
            'videoSessions' => [
                '1' => [
                    '上午班' => [
                        'dates' => ['2026-09-08'],
                        'start' => '9:00',
                        'end' => '10:50',
                    ],
                    '下午班' => [
                        'dates' => ['2026-09-08'],
                        'start' => '14:00',
                        'end' => '15:50',
                    ],
                    '夜間班' => [
                        'dates' => ['2026-09-08'],
                        'start' => '19:00',
                        'end' => '20:50',
                    ],
                ],
            ],
            'exam' => [
                '微學分' => ['1'],
            ],
        ],
    ]);

    File::shouldReceive('get')
        ->with(resource_path('data/course-select-sim.json'))
        ->andReturn($examJson);

    $result = (new ImportCourseSelectSimulation)('2026A');

    expect($result['classes'])->toBe(3);

    $course = Course::query()->where('name', '我的未來學習')->where('term', '2026A')->first();
    $classes = CourseClass::query()->where('course_id', $course->id)->get();

    expect($classes)->toHaveCount(3);
    expect($classes->pluck('type')->unique()->all())->toBe([CourseClassType::MicroCredit]);
    expect($classes->pluck('is_tentative')->unique()->all())->toBe([true]);

    $morning = $classes->firstWhere('code', 'NOTICE-MORNING');
    expect($morning->start_time)->toBe('9:00');
    expect($morning->end_time)->toBe('10:50');

    // The stored type is overridden to micro_credit, but the class was
    // actually held in the morning per its NOTICE-MORNING code, so the
    // editor should still label it as such rather than "微學分".
    expect(ScheduleEditorCourseClassViewModel::fromModel($morning)->typeLabel)->toBe('上午班');
});
