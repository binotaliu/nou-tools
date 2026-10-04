<?php

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\Program;
use App\Models\StudentSchedule;

// 專班生 add the school's ready-made course set from the editor's picker; the
// courses carry the 專班's own class, so there is no class to choose.

function browserProgramClass(Program $program, string $courseName): CourseClass
{
    $course = Course::query()->firstOrCreate(
        ['term' => '2025B', 'name' => $courseName],
        ['is_special_program_only' => true],
    );

    return CourseClass::factory()->for($course)->create([
        'program_id' => $program->id,
        'code' => $program->name,
        'type' => CourseClassType::SpecialProgram,
    ]);
}

it('adds a 專班\'s courses, keeps their class and saves the schedule', function () {
    config()->set('app.current_semester', '2025B');

    $program = Program::factory()->create(['term' => '2025B', 'region' => 'tc', 'name' => '測試甲專班']);
    $first = browserProgramClass($program, '測試課程甲');
    $second = browserProgramClass($program, '測試課程乙');

    visit(route('schedules.create', ['term' => '2025B']))
        ->assertSee('已選 0 / 14 門課程')
        ->assertPresent('[data-testid="program-add"][disabled]')
        ->select('[data-testid="program-region"]', 'tc')
        ->select('[data-testid="program-name"]', (string) $program->id)
        ->click('[data-testid="program-add"]')
        ->assertSee('已選 2 / 14 門課程')
        ->click('[data-testid="schedule-next"]')
        ->assertPresent('[data-testid="selected-item-'.$first->course_id.'"] [data-testid="program-class-note"]')
        ->assertPresent('[data-testid="selected-item-'.$second->course_id.'"] [data-testid="program-class-note"]')
        ->fill('#schedule-name', '專班瀏覽器課表')
        ->click('[data-testid="schedule-submit"]')
        ->assertSee('課表已保存！');

    $schedule = StudentSchedule::where('name', '專班瀏覽器課表')->firstOrFail();

    expect($schedule->items()->orderBy('course_id')->pluck('course_class_id')->all())
        ->toBe([$first->id, $second->id]);
});

it('swaps an already picked 一般課程 for the 專班\'s class instead of duplicating it', function () {
    config()->set('app.current_semester', '2025B');

    $general = Course::factory()->create(['term' => '2025B', 'name' => '共用課程']);
    CourseClass::factory()->for($general)->create(['type' => CourseClassType::Morning]);

    $program = Program::factory()->create(['term' => '2025B', 'region' => 'tc', 'name' => '測試甲專班']);
    $programClass = CourseClass::factory()->for($general)->create([
        'program_id' => $program->id,
        'code' => $program->name,
        'type' => CourseClassType::SpecialProgram,
    ]);

    visit(route('schedules.create', ['term' => '2025B']))
        ->click('[data-testid="course-checkbox-'.$general->id.'"]')
        ->assertSee('已選 1 / 14 門課程')
        ->select('[data-testid="program-region"]', 'tc')
        ->select('[data-testid="program-name"]', (string) $program->id)
        ->click('[data-testid="program-add"]')
        ->assertSee('已選 1 / 14 門課程')
        ->click('[data-testid="schedule-next"]')
        ->assertPresent('[data-testid="selected-item-'.$general->id.'"] [data-testid="program-class-note"]')
        ->click('[data-testid="schedule-submit"]')
        ->assertSee('課表已保存！');

    expect(StudentScheduleItemClassIds())->toBe([$programClass->id]);
});

it('opens a saved 專班 schedule on the class step without dropping its courses', function () {
    config()->set('app.current_semester', '2025B');

    $program = Program::factory()->create(['term' => '2025B', 'region' => 'tc', 'name' => '測試甲專班']);
    $class = browserProgramClass($program, '測試課程甲');

    $schedule = StudentSchedule::factory()->create();
    $schedule->items()->create(['course_id' => $class->course_id, 'course_class_id' => $class->id]);

    visit(route('schedules.edit', $schedule))
        ->assertPresent('[data-testid="selected-item-'.$class->course_id.'"] [data-testid="program-class-note"]')
        ->click('[data-testid="schedule-submit"]')
        ->assertSee('課表已更新！');

    expect($schedule->items()->pluck('course_class_id')->all())->toBe([$class->id]);
});

function StudentScheduleItemClassIds(): array
{
    return StudentSchedule::query()->firstOrFail()->items()->pluck('course_class_id')->all();
}
