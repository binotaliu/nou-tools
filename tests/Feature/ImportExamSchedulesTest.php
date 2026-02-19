<?php

use App\Models\Course;
use App\Services\ExamScheduleService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create test courses
    Course::factory()->create([
        'name' => '做伙唱歌學台語',
        'term' => '2025B',
    ]);

    Course::factory()->create([
        'name' => '三國演義',
        'term' => '2025B',
    ]);

    // Course with special characters - should match via normalization
    Course::factory()->create([
        'name' => '當代治理新趨勢（二）：理論與個案',
        'term' => '2025B',
    ]);

    // Non-matching course
    Course::factory()->create([
        'name' => '某個不存在的課程',
        'term' => '2025B',
    ]);

    File::shouldReceive('exists')
        ->with(resource_path('data/exams.json'))
        ->andReturnTrue()
        ->byDefault();

    File::shouldReceive('get')
        ->with(resource_path('data/exams.json'))
        ->andReturn(json_encode([
            '2025B' => [
                'label' => '114 下學期',
                'dates' => [
                    'saturday' => ['midterm' => '2025-04-25', 'final' => '2025-06-27'],
                    'sunday' => ['midterm' => '2025-04-26', 'final' => '2025-06-28'],
                ],
                'slots' => [
                    [
                        'time' => '13:30-14:40',
                        'saturday' => [
                            ['title' => '做伙唱歌學台語'],
                            ['title' => '三國演義'],
                        ],
                        'sunday' => [],
                    ],
                    [
                        'time' => '08:30-09:40',
                        'saturday' => [],
                        'sunday' => [
                            ['title' => '當代治理新趨勢（二）：理論與個案'],
                        ],
                    ],
                ],
            ],
        ]))
        ->byDefault();
});

it('imports exam schedules correctly', function () {
    $this->artisan('exam:import', ['term' => '2025B'])
        ->assertSuccessful()
        ->expectsOutput('Matched: 3 courses');

    // Check that exam dates were assigned
    $course = Course::where('name', '做伙唱歌學台語')->first();
    expect($course->midterm_date)->toBe('2025-04-25');
    expect($course->final_date)->toBe('2025-06-27');
    expect($course->exam_time_start)->toBe('13:30');
    expect($course->exam_time_end)->toBe('14:40');

    // Check Saturday course "三國演義"
    $sunday = Course::where('name', '三國演義')->first();
    expect($sunday->midterm_date)->toBe('2025-04-25');
    expect($sunday->final_date)->toBe('2025-06-27');
    expect($sunday->exam_time_start)->toBe('13:30');
    expect($sunday->exam_time_end)->toBe('14:40');
});

it('matches courses with special characters via normalization', function () {
    $course = Course::where('name', '當代治理新趨勢（二）：理論與個案')->first();
    expect($course->midterm_date)->toBeNull();

    $this->artisan('exam:import', ['term' => '2025B'])
        ->assertSuccessful();

    $course->refresh();
    expect($course->midterm_date)->toBe('2025-04-26');
    expect($course->final_date)->toBe('2025-06-28');
    expect($course->exam_time_start)->toBe('08:30');
    expect($course->exam_time_end)->toBe('09:40');
});

it('fails with invalid term', function () {
    $this->artisan('exam:import', ['term' => 'INVALID'])
        ->assertFailed()
        ->expectsOutput('No exam schedule data found for term: INVALID');
});

it('uses file facade for reading exam schedules', function () {
    File::shouldReceive('exists')
        ->once()
        ->with(resource_path('data/exams.json'))
        ->andReturnTrue();

    File::shouldReceive('get')
        ->once()
        ->with(resource_path('data/exams.json'))
        ->andReturn(json_encode([
            '2025B' => [
                'label' => '114 下學期',
                'dates' => [
                    'saturday' => ['midterm' => '2025-04-25', 'final' => '2025-06-27'],
                    'sunday' => ['midterm' => '2025-04-26', 'final' => '2025-06-28'],
                ],
                'slots' => [],
            ],
        ]));

    $service = new ExamScheduleService;
    $result = $service->import('2025B');

    expect($result['success'])->toBe(0);
    expect($result['failed'])->toBe(0);
});

it('normalizes course names correctly', function () {
    $service = new ExamScheduleService;

    expect($service->normalizeName('做伙唱歌學台語'))->toBe('做伙唱歌學台語');
    expect($service->normalizeName('當代治理新趨勢（二）：理論與個案'))->toBe('當代治理新趨勢二理論與個案');
    expect($service->normalizeName('課程 名稱（test）：內容～資訊'))->toBe('課程名稱test內容資訊');
    expect($service->normalizeName('  spaces  '))->toBe('spaces');
});
