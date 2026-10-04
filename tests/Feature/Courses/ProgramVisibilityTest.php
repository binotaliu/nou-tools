<?php

use App\Enums\CourseClassType;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Program;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withoutVite;

$programClassFor = function (Course $course, ?Program $program = null): CourseClass {
    return CourseClass::factory()->for($course)->create([
        'program_id' => ($program ?? Program::factory()->create())->id,
        'type' => CourseClassType::SpecialProgram,
    ]);
};

beforeEach(function () {
    withoutVite();
    config(['app.current_semester' => '2026A']);
});

it('includes 專班-only courses in the course list API', function () use ($programClassFor) {
    Course::factory()->create(['term' => '2026A', 'name' => '一般課程']);
    $programClassFor(Course::factory()->create(['term' => '2026A', 'name' => '專班專屬課程', 'is_special_program_only' => true]));

    getJson('/api/v1/courses')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['name' => '一般課程', 'isSpecialProgramOnly' => false])
        ->assertJsonFragment(['name' => '專班專屬課程', 'isSpecialProgramOnly' => true]);
});

it('includes 專班 classes in the course detail API', function () use ($programClassFor) {
    $course = Course::factory()->create(['term' => '2026A']);
    $general = CourseClass::factory()->for($course)->create(['code' => 'ZZZ900', 'type' => CourseClassType::Morning]);
    $program = $programClassFor($course, Program::factory()->create(['name' => '某某專班']));
    ClassSchedule::factory()->for($program, 'courseClass')->create(['date' => '2026-10-17']);

    getJson("/api/v1/courses/{$course->id}")
        ->assertOk()
        ->assertJsonCount(2, 'classes')
        ->assertJsonFragment(['id' => $general->id, 'programName' => null])
        ->assertJsonFragment(['id' => $program->id, 'programName' => '某某專班', 'type' => 'special_program']);
});

it('keeps 專班-only courses and classes out of the schedule editor', function () use ($programClassFor) {
    $shared = Course::factory()->create(['term' => '2026A', 'name' => '共用課程']);
    $generalClass = CourseClass::factory()->for($shared)->create(['type' => CourseClassType::Morning]);
    $programClassFor($shared);
    $programClassFor(Course::factory()->create(['term' => '2026A', 'name' => '專班專屬課程', 'is_special_program_only' => true]));

    $this->get(route('schedules.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Schedule/Editor')
            ->has('viewModel.courses', 1)
            ->where('viewModel.courses.0.name', '共用課程')
            ->has('viewModel.courses.0.classes', 1)
            ->where('viewModel.courses.0.classes.0.id', $generalClass->id));
});

it('keeps 專班-only courses and classes out of the course schedule page', function () use ($programClassFor) {
    Course::factory()->create(['term' => '2026A', 'name' => '一般課程']);
    $programClassFor(Course::factory()->create(['term' => '2026A', 'name' => '專班專屬課程', 'is_special_program_only' => true]));

    $this->get(route('course.schedule'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('viewModel.microCreditOrRemoteCourses', 1)
            ->where('viewModel.microCreditOrRemoteCourses.0.name', '一般課程'));
});

it('keeps 專班 classes off the course detail page and the sitemap', function () use ($programClassFor) {
    $course = Course::factory()->create(['term' => '2026A']);
    CourseClass::factory()->for($course)->create(['code' => 'ZZZ900', 'type' => CourseClassType::Morning]);
    $programClassFor($course, Program::factory()->create(['name' => '某某專班']));
    $only = Course::factory()->create(['term' => '2026A', 'is_special_program_only' => true]);

    $this->get(route('course.show', $course))
        ->assertOk()
        ->assertSee('ZZZ900')
        ->assertDontSee('某某專班');

    $this->get(route('sitemap'))
        ->assertSee(route('course.show', $course), false)
        ->assertDontSee(route('course.show', $only), false);
});

it('keeps 專班 classes out of today\'s video courses', function () use ($programClassFor) {
    $today = Carbon::now('Asia/Taipei')->format('Y-m-d');
    $course = Course::factory()->create(['term' => '2026A', 'name' => '今日課程']);
    $class = $programClassFor($course);
    ClassSchedule::factory()->for($class, 'courseClass')->create(['date' => $today]);

    $this->get(route('video-classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('viewModel.courses', 0));
});
