<?php

use App\Enums\CourseClassType;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Program;
use App\Models\StudentSchedule;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\withoutVite;

$programWithClass = function (string $programName, string $courseName, int $position = 0): CourseClass {
    $program = Program::query()->firstOrCreate(
        ['term' => '2026A', 'name' => $programName],
        ['region' => 'tc', 'position' => $position],
    );

    $course = Course::query()->firstOrCreate(
        ['term' => '2026A', 'name' => $courseName],
        ['is_special_program_only' => true],
    );

    return CourseClass::factory()->for($course)->create([
        'program_id' => $program->id,
        'code' => $programName,
        'type' => CourseClassType::SpecialProgram,
    ]);
};

beforeEach(function () {
    withoutVite();
    config(['app.current_semester' => '2026A']);
});

it('lists the term\'s 專班 with one fixed class per course for the editor', function () use ($programWithClass) {
    $first = $programWithClass('測試甲專班', '測試課程乙');
    $programWithClass('測試甲專班', '測試課程甲');
    $programWithClass('測試乙專班', '測試課程丙', 1);
    Program::factory()->create(['term' => '2025B', 'name' => '舊學期專班']);

    $this->get(route('schedules.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Schedule/Editor')
            ->has('viewModel.programs', 2)
            ->where('viewModel.programs.0.name', '測試甲專班')
            ->where('viewModel.programs.0.region', 'tc')
            ->where('viewModel.programs.0.region_label', '台中')
            ->has('viewModel.programs.0.courses', 2)
            ->where('viewModel.programs.0.courses.0.name', '測試課程乙')
            ->where('viewModel.programs.0.courses.1.name', '測試課程甲')
            ->has('viewModel.programs.0.courses.0.classes', 1)
            ->where('viewModel.programs.0.courses.0.classes.0.id', $first->id)
            ->where('viewModel.programs.0.courses.0.classes.0.type', 'special_program'));
});

it('keeps a shared course\'s other 專班 classes out of each 專班 entry', function () use ($programWithClass) {
    $a = $programWithClass('測試甲專班', '共用課程');
    $b = $programWithClass('測試乙專班', '共用課程', 1);

    $this->get(route('schedules.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.programs.0.courses.0.classes.0.id', $a->id)
            ->has('viewModel.programs.0.courses.0.classes', 1)
            ->where('viewModel.programs.1.courses.0.classes.0.id', $b->id)
            ->has('viewModel.programs.1.courses.0.classes', 1));
});

it('creates a schedule from 專班 classes and shows their dates', function () use ($programWithClass) {
    $class = $programWithClass('測試甲專班', '測試課程甲');
    ClassSchedule::factory()->for($class, 'courseClass')->create(['date' => '2026-10-17']);

    $this->postJson(route('schedules.store'), [
        'name' => '專班課表',
        'term' => '2026A',
        'items' => [['course_id' => $class->course_id, 'class_id' => $class->id]],
    ])->assertOk();

    $schedule = StudentSchedule::query()->firstOrFail();

    expect($schedule->items()->where('course_class_id', $class->id)->exists())->toBeTrue();
});

it('loads saved 專班 items into the editor', function () use ($programWithClass) {
    $class = $programWithClass('測試甲專班', '測試課程甲');

    $this->postJson(route('schedules.store'), [
        'term' => '2026A',
        'items' => [['course_id' => $class->course_id, 'class_id' => $class->id]],
    ])->assertOk();

    $schedule = StudentSchedule::query()->firstOrFail();

    $this->get(route('schedules.edit', $schedule))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.selectedItems.0.classId', $class->id)
            ->where('viewModel.programs.0.courses.0.classes.0.id', $class->id));
});

it('rejects a course picked twice', function () {
    $general = CourseClass::factory()->create(['type' => CourseClassType::Morning]);
    $program = CourseClass::factory()->for($general->course)->create([
        'program_id' => Program::factory()->create(['term' => $general->course->term])->id,
        'type' => CourseClassType::SpecialProgram,
    ]);

    $this->postJson(route('schedules.store'), [
        'term' => $general->course->term,
        'items' => [
            ['course_id' => $general->course_id, 'class_id' => $general->id],
            ['course_id' => $general->course_id, 'class_id' => $program->id],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.course_id', 'items.1.course_id']);
});
