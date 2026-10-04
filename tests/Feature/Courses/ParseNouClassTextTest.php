<?php

use NouTools\Domains\Courses\Support\ParseNouClassText;

beforeEach(function () {
    $this->text = new ParseNouClassText;
});

it('reads time ranges with and without colons', function (string $input, array $expected) {
    expect($this->text->time($input))->toBe($expected);
})->with([
    'colon and tilde' => ['時間：19:00~20:50', ['start' => '19:00', 'end' => '20:50']],
    'compact and dash' => ['時間：0800-0940', ['start' => '08:00', 'end' => '09:40']],
    'compact and tilde' => ['時間：1550~1730', ['start' => '15:50', 'end' => '17:30']],
]);

it('returns null when there is no time', function () {
    expect($this->text->time('時間：待定'))->toBeNull();
});

it('reads per-session time changes in every notation', function () {
    $text = '時間：1550-1730<br>第二次改1400-1540<br>第一、三次：19:00-20:50<br>第四次改1020-1200';

    expect($this->text->sessionTimeOverrides($text))->toBe([
        2 => ['start_time' => '14:00', 'end_time' => '15:40'],
        1 => ['start_time' => '19:00', 'end_time' => '20:50'],
        3 => ['start_time' => '19:00', 'end_time' => '20:50'],
        4 => ['start_time' => '10:20', 'end_time' => '12:00'],
    ]);
});

it('reads Chinese numeral lists of sessions', function () {
    expect($this->text->sessionTimeOverrides('第一、二次改1550-1730'))->toBe([
        1 => ['start_time' => '15:50', 'end_time' => '17:30'],
        2 => ['start_time' => '15:50', 'end_time' => '17:30'],
    ]);
});

it('extracts teacher names, dates and course names', function () {
    expect($this->text->teacherName("測試教師甲老師\n 備註"))->toBe('測試教師甲老師')
        ->and($this->text->dates('09/19、10/17、11/21'))->toBe(['09/19', '10/17', '11/21'])
        ->and($this->text->courseName('01.測試課程甲 '))->toBe('測試課程甲')
        ->and($this->text->courseName('02.測試課程乙(夜間班)'))->toBe('測試課程乙')
        ->and($this->text->courseName('沒有編號'))->toBe('');
});
