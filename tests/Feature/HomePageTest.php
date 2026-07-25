<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Carbon\Carbon;

test('homepage returns Link headers for API discovery', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200)
        ->assertHeader('Link', implode(', ', [
            '<'.route('docs.api.view').'>; rel="service-doc"',
            '<'.route('docs.api.yaml').'>; rel="service-desc"',
        ]));
});

test('homepage lists courses with in-person classes scheduled for the selected date', function () {
    $selectedDate = Carbon::now('Asia/Taipei')->format('Y-m-d');

    $course = Course::factory()->create(['name' => '普通物理學']);
    $courseClass = CourseClass::factory()->for($course)->create(['code' => 'zzz001']);
    ClassSchedule::factory()->for($courseClass, 'courseClass')->create(['date' => $selectedDate]);

    // A course with no class scheduled for today should not appear.
    $otherCourse = Course::factory()->create(['name' => '不面授的課']);
    $otherClass = CourseClass::factory()->for($otherCourse)->create();
    ClassSchedule::factory()->for($otherClass, 'courseClass')->create(['date' => Carbon::parse($selectedDate)->addMonth()]);

    $response = $this->get(route('home'));

    $response->assertStatus(200)
        ->assertSee('普通物理學')
        ->assertSee('ZZZ001')
        ->assertDontSee('不面授的課');
});
