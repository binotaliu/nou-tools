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
    expect($courses[0]['classes'][0]['schedule_time_overrides'])->toBeEmpty();

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
})->skip();

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
})->skip();

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
})->skip();

it('returns empty array for empty HTML', function () {
    $courses = $this->parser->parse('', CourseClassType::Morning);

    expect($courses)->toBeEmpty();
});

it('returns empty array for HTML without course cards', function () {
    $html = '<html><body><div>No courses here</div></body></html>';

    $courses = $this->parser->parse($html, CourseClassType::Morning);

    expect($courses)->toBeEmpty();
});

it('parses micro-credit courses from vc4 micro sample', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_micro_sample.html');

    $courses = $this->parser->parse($html, CourseClassType::MicroCredit);

    expect($courses)->toHaveCount(3);

    expect($courses[0]['name'])->toBe('空大數位學習環境的操作與應用');
    expect($courses[0]['classes'])->toHaveCount(2);
    expect($courses[0]['classes'][0]['code'])->toBe('zzz001');
    expect($courses[0]['classes'][0]['type'])->toBe(CourseClassType::MicroCredit);
    expect($courses[0]['classes'][0]['start_time'])->toBe('19:00');
    expect($courses[0]['classes'][0]['end_time'])->toBe('20:50');
    expect($courses[0]['classes'][0]['teacher_name'])->toBe('郭秋田老師');
    expect($courses[0]['classes'][0]['dates'])->toBe(['02/25', '03/25', '04/22']);
    expect($courses[0]['classes'][0]['schedule_time_overrides'])->toBeEmpty();

    expect($courses[0]['classes'][1]['code'])->toBe('zzz002');
    expect($courses[0]['classes'][1]['teacher_name'])->toBe('黃俊宏老師');
});

it('filters sections correctly so full remote does not include micro-credit', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_micro_sample.html');

    $fullRemoteCourses = $this->parser->parse($html, CourseClassType::FullRemote);
    $microCreditCourses = $this->parser->parse($html, CourseClassType::MicroCredit);

    expect($fullRemoteCourses)->toHaveCount(1);
    expect($fullRemoteCourses[0]['name'])->toBe('閱讀英文學文化');

    expect($microCreditCourses)->toHaveCount(3);
    expect($microCreditCourses[0]['name'])->toBe('空大數位學習環境的操作與應用');
});

it('parses session time overrides for micro-credit courses', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_micro_sample.html');

    $courses = $this->parser->parse($html, CourseClassType::MicroCredit);

    $germanLaw3 = $courses[1];
    expect($germanLaw3['name'])->toBe('法學德文（三）');
    expect($germanLaw3['classes'])->toHaveCount(1);

    $class = $germanLaw3['classes'][0];
    expect($class['start_time'])->toBe('09:10');
    expect($class['end_time'])->toBe('12:00');
    expect($class['dates'])->toBe(['02/24', '03/03', '03/17', '03/24', '04/07']);

    expect($class['schedule_time_overrides'])->toBe([
        1 => ['start_time' => '18:30', 'end_time' => '21:00'],
        2 => ['start_time' => '18:30', 'end_time' => '21:00'],
    ]);
});

it('does not have time overrides for courses with single time', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4_micro_sample.html');

    $courses = $this->parser->parse($html, CourseClassType::MicroCredit);

    $germanLaw4 = $courses[2];
    expect($germanLaw4['name'])->toBe('法學德文（四）');
    expect($germanLaw4['classes'][0]['start_time'])->toBe('09:10');
    expect($germanLaw4['classes'][0]['end_time'])->toBe('12:00');
    expect($germanLaw4['classes'][0]['schedule_time_overrides'])->toBeEmpty();
});

it('parses real vc4 HTML file for micro-credit courses', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4.html');

    $courses = $this->parser->parse($html, CourseClassType::MicroCredit);

    expect($courses)->not->toBeEmpty();

    foreach ($courses as $course) {
        expect($course['name'])->not->toBeEmpty();
        expect($course['classes'])->not->toBeEmpty();

        foreach ($course['classes'] as $class) {
            expect($class['code'])->toStartWith('zzz');
            expect($class['type'])->toBe(CourseClassType::MicroCredit);
            expect($class['teacher_name'])->not->toBeEmpty();
            expect($class['link'])->not->toBeEmpty();
            expect($class['dates'])->not->toBeEmpty();
        }
    }
})->skip();

it('parses real vc4 HTML file with time overrides for 法學德文（三）', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/vc4.html');

    $courses = $this->parser->parse($html, CourseClassType::MicroCredit);

    $germanLaw = collect($courses)->firstWhere('name', '法學德文（三）');
    expect($germanLaw)->not->toBeNull();

    $class = $germanLaw['classes'][0];
    expect($class['start_time'])->toBe('09:10');
    expect($class['end_time'])->toBe('12:00');
    expect($class['schedule_time_overrides'])->not->toBeEmpty();
    expect($class['schedule_time_overrides'][1]['start_time'])->toBe('18:30');
    expect($class['schedule_time_overrides'][1]['end_time'])->toBe('21:00');
})->skip();
