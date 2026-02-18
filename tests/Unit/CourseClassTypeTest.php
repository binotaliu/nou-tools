<?php

use App\Enums\CourseClassType;

it('has four cases', function () {
    expect(CourseClassType::cases())->toHaveCount(4);
});

it('has correct string values', function (CourseClassType $type, string $value) {
    expect($type->value)->toBe($value);
})->with([
    [CourseClassType::Morning, 'morning'],
    [CourseClassType::Afternoon, 'afternoon'],
    [CourseClassType::Evening, 'evening'],
    [CourseClassType::FullRemote, 'full_remote'],
]);

it('returns correct labels', function (CourseClassType $type, string $label) {
    expect($type->label())->toBe($label);
})->with([
    [CourseClassType::Morning, '上午班'],
    [CourseClassType::Afternoon, '下午班'],
    [CourseClassType::Evening, '夜間班'],
    [CourseClassType::FullRemote, '全遠距'],
]);

it('returns correct default time slots', function (CourseClassType $type, ?array $expected) {
    expect($type->defaultTimeSlot())->toBe($expected);
})->with([
    [CourseClassType::Morning, ['start' => '09:00', 'end' => '10:50']],
    [CourseClassType::Afternoon, ['start' => '14:00', 'end' => '15:50']],
    [CourseClassType::Evening, ['start' => '19:00', 'end' => '20:50']],
    [CourseClassType::FullRemote, null],
]);
