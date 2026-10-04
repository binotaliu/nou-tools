<?php

use NouTools\Domains\Courses\Actions\ParseNouProgramCourses;

beforeEach(function () {
    $this->programs = (new ParseNouProgramCourses)(
        file_get_contents(__DIR__.'/../../Fixtures/svc_sample.html'),
    );
});

it('splits the page into programs by heading, ignoring the navigation', function () {
    expect(array_column($this->programs, 'name'))->toBe([
        '測試01(115-1)甲專班-視訊',
        '測試02乙專班-視訊',
    ])->and(array_map(fn (array $program): int => count($program['courses']), $this->programs))->toBe([3, 2]);
});

it('reads a plain card', function () {
    expect($this->programs[0]['courses'][0])->toBe([
        'name' => '測試課程甲',
        'start_time' => '08:00',
        'end_time' => '09:40',
        'teacher_name' => '測試教師甲老師',
        'link' => 'https://example.test/webex/program-a',
        'dates' => ['09/19', '10/17', '11/21', '12/05'],
        'schedule_time_overrides' => [],
    ]);
});

it('reads per-session time changes', function () {
    $changed = $this->programs[0]['courses'][1];

    expect($changed['start_time'])->toBe('12:10')
        ->and($changed['schedule_time_overrides'])->toBe([
            2 => ['start_time' => '14:00', 'end_time' => '15:40'],
        ]);

    expect($this->programs[1]['courses'][0]['schedule_time_overrides'])->toBe([
        1 => ['start_time' => '14:00', 'end_time' => '15:40'],
        2 => ['start_time' => '14:00', 'end_time' => '15:40'],
    ]);
});

it('copes with card-title2 typos, colon times and trailing spaces', function () {
    $course = $this->programs[0]['courses'][2];

    expect($course['name'])->toBe('測試課程丙')
        ->and($course['start_time'])->toBe('19:00')
        ->and($course['end_time'])->toBe('20:50');
});

it('strips a time-slot suffix from the course name', function () {
    expect($this->programs[1]['courses'][1]['name'])->toBe('測試課程丁');
});

it('returns nothing for an empty page', function () {
    expect((new ParseNouProgramCourses)(''))->toBe([]);
});
