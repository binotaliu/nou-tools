<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

function videoClassOn(string $courseName, string $date, string $code): void
{
    $course = Course::factory()->create(['name' => $courseName]);
    $class = CourseClass::factory()->for($course)->create(['code' => $code]);
    ClassSchedule::factory()->for($class, 'courseClass')->create(['date' => $date]);
}

test('standalone page lists today\'s video courses by default', function () {
    $today = Carbon::now('Asia/Taipei')->format('Y-m-d');
    videoClassOn('普通物理學', $today, 'aaa001');
    videoClassOn('明天的課', Carbon::parse($today)->addDay()->format('Y-m-d'), 'bbb001');

    $this->get(route('video-classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('VideoClasses/Index')
            ->where('viewModel.selectedDate', $today)
            ->where('viewModel.today', $today)
            ->has('viewModel.courses', 1)
            ->where('viewModel.courses.0.name', '普通物理學'));
});

test('standalone page honours ?date= and falls back to today on invalid input', function () {
    $today = Carbon::now('Asia/Taipei')->format('Y-m-d');
    videoClassOn('指定日的課', '2026-03-05', 'ccc001');

    $this->get(route('video-classes.index', ['date' => '2026-03-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.selectedDate', '2026-03-05')
            ->where('viewModel.courses.0.name', '指定日的課'));

    $this->get(route('video-classes.index', ['date' => 'nonsense']))
        ->assertInertia(fn (Assert $page) => $page->where('viewModel.selectedDate', $today));
});

test('standalone page excludes tentative classes', function () {
    $today = Carbon::now('Asia/Taipei')->format('Y-m-d');
    $course = Course::factory()->create(['name' => '暫定的課']);
    $class = CourseClass::factory()->for($course)->create(['code' => 'ddd001', 'is_tentative' => true]);
    ClassSchedule::factory()->for($class, 'courseClass')->create(['date' => $today]);

    $this->get(route('video-classes.index'))
        ->assertInertia(fn (Assert $page) => $page->has('viewModel.courses', 0));
});

test('markdown twin lists the day\'s courses', function () {
    videoClassOn('普通物理學', '2026-03-05', 'eee001');

    $this->get(route('video-classes.index.md', ['date' => '2026-03-05']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# 今日視訊面授')
        ->assertSee('普通物理學')
        ->assertSee('EEE001');
});
