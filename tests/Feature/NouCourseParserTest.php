<?php

use App\Enums\CourseClassType;
use App\Services\NouCourseParser;

beforeEach(function () {
    $this->parser = new NouCourseParser;
});

it('parses morning class courses from vc1 sample', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc1_sample.html');

    $courses = $this->parser->parse($html, CourseClassType::Morning);

    expect($courses)->toHaveCount(2);

    expect($courses[0]['name'])->toBe('做伙唱歌學台語');
    expect($courses[0]['classes'])->toHaveCount(1);
    expect($courses[0]['classes'][0]['code'])->toBe('zzz201');
    expect($courses[0]['classes'][0]['type'])->toBe(CourseClassType::Morning);
    expect($courses[0]['classes'][0]['start_time'])->toBe('09:00');
    expect($courses[0]['classes'][0]['end_time'])->toBe('10:50');
    expect($courses[0]['classes'][0]['teacher_name'])->toBe('蔡惠名老師');
    expect($courses[0]['classes'][0]['link'])->toContain('webex.com');
    expect($courses[0]['classes'][0]['dates'])->toBe(['03/09', '03/30', '05/11', '06/08']);

    expect($courses[1]['name'])->toBe('愛情心理學');
    expect($courses[1]['classes'][0]['teacher_name'])->toBe('洪敏琬老師');
});

it('parses evening class courses with multiple classes per course from vc3 sample', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc3_sample.html');

    $courses = $this->parser->parse($html, CourseClassType::Evening);

    expect($courses)->toHaveCount(1);
    expect($courses[0]['name'])->toBe('做伙唱歌學台語');
    expect($courses[0]['classes'])->toHaveCount(3);

    expect($courses[0]['classes'][0]['code'])->toBe('zzz001');
    expect($courses[0]['classes'][0]['teacher_name'])->toBe('蔡惠名老師');
    expect($courses[0]['classes'][0]['start_time'])->toBe('19:00');
    expect($courses[0]['classes'][0]['end_time'])->toBe('20:50');

    expect($courses[0]['classes'][1]['code'])->toBe('zzz002');
    expect($courses[0]['classes'][1]['teacher_name'])->toBe('王桂蘭老師');

    expect($courses[0]['classes'][2]['code'])->toBe('zzz003');
    expect($courses[0]['classes'][2]['teacher_name'])->toBe('劉沛慈老師');
});

it('parses full remote courses with custom time from vc4 sample', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_sample.html');

    $courses = $this->parser->parse($html, CourseClassType::FullRemote);

    expect($courses)->toHaveCount(2);

    expect($courses[0]['name'])->toBe('閱讀英文學文化');
    expect($courses[0]['classes'])->toHaveCount(2);
    expect($courses[0]['classes'][0]['start_time'])->toBe('19:00');
    expect($courses[0]['classes'][0]['end_time'])->toBe('20:50');

    expect($courses[1]['name'])->toBe('家族史與數位人文實作');
    expect($courses[1]['classes'])->toHaveCount(1);
    expect($courses[1]['classes'][0]['start_time'])->toBe('18:30');
    expect($courses[1]['classes'][0]['end_time'])->toBe('21:10');
    expect($courses[1]['classes'][0]['dates'])->toHaveCount(6);
    expect($courses[1]['classes'][0]['dates'])->toBe(['03/12', '03/26', '04/16', '05/07', '05/21', '06/11']);
});

it('extracts teacher name correctly even with complex format', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_sample.html');

    $courses = $this->parser->parse($html, CourseClassType::FullRemote);

    $familyHistory = $courses[1];
    expect($familyHistory['classes'][0]['teacher_name'])->toBe('沈佳姍老師');
});

it('parses real vc1 HTML file', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc1.html');

    $courses = $this->parser->parse($html, CourseClassType::Morning);

    expect($courses)->not->toBeEmpty();

    foreach ($courses as $course) {
        expect($course['name'])->not->toBeEmpty();
        expect($course['classes'])->not->toBeEmpty();

        foreach ($course['classes'] as $class) {
            expect($class['code'])->toStartWith('zzz');
            expect($class['start_time'])->toBe('09:00');
            expect($class['end_time'])->toBe('10:50');
            expect($class['teacher_name'])->not->toBeEmpty();
            expect($class['link'])->not->toBeEmpty();
            expect($class['dates'])->not->toBeEmpty();
        }
    }
});

it('parses real vc3 HTML file with multiple classes per course', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc3.html');

    $courses = $this->parser->parse($html, CourseClassType::Evening);

    expect($courses)->not->toBeEmpty();

    $hasMultipleClasses = false;

    foreach ($courses as $course) {
        if (count($course['classes']) > 1) {
            $hasMultipleClasses = true;
        }
    }

    expect($hasMultipleClasses)->toBeTrue();
});

it('parses real vc4 HTML file with varied time slots', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4.html');

    $courses = $this->parser->parse($html, CourseClassType::FullRemote);

    expect($courses)->not->toBeEmpty();

    $times = collect($courses)->flatMap(function ($course) {
        return collect($course['classes'])->map(function ($class) {
            return $class['start_time'].'-'.$class['end_time'];
        });
    })->unique()->values()->all();

    expect(count($times))->toBeGreaterThan(1);
});

it('returns empty array for empty HTML', function () {
    $courses = $this->parser->parse('', CourseClassType::Morning);

    expect($courses)->toBeEmpty();
});

it('returns empty array for HTML without course cards', function () {
    $html = '<html><body><div>No courses here</div></body></html>';

    $courses = $this->parser->parse($html, CourseClassType::Morning);

    expect($courses)->toBeEmpty();
});
