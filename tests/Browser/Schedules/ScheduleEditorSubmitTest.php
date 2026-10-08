<?php

use App\Models\Course;
use App\Models\StudentSchedule;

// The editor submits through Inertia's useForm(), building `items` from its
// selected-course state, so a real browser is the only place that proves the
// payload reaches ScheduleController::store() and the redirect is followed.

it('creates a schedule from the selected courses and lands on its page', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create(['term' => '2025B', 'name' => '瀏覽器測試課程']);

    visit(route('schedules.create', ['term' => '2025B']))
        ->fill('#search', '瀏覽器測試')
        ->click('[data-testid="course-checkbox-'.$course->id.'"]')
        ->click('[data-testid="schedule-next"]')
        ->fill('#schedule-name', 'Inertia Editor Schedule')
        ->click('[data-testid="schedule-submit"]')
        ->assertSee('課表已保存！')
        ->assertSee('瀏覽器測試課程');

    $schedule = StudentSchedule::where('name', 'Inertia Editor Schedule')->firstOrFail();

    expect($schedule->items()->pluck('course_id')->all())->toBe([$course->id]);
});

it('keeps the picked courses when going back from the class step', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create(['term' => '2025B', 'name' => '上一步測試課程']);

    visit(route('schedules.create', ['term' => '2025B']))
        ->assertSee('已選 0 / 14 門課程')
        ->click('[data-testid="course-checkbox-'.$course->id.'"]')
        ->assertSee('已選 1 / 14 門課程')
        ->click('[data-testid="schedule-next"]')
        ->assertPresent('[data-testid="selected-item-'.$course->id.'"]')
        ->click('[data-testid="schedule-back"]')
        ->assertChecked('[data-testid="course-checkbox-'.$course->id.'"]');
});

it('opens an existing schedule on the course step with its courses checked', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create(['term' => '2025B', 'name' => '編輯測試課程']);
    $schedule = StudentSchedule::factory()->create();
    $schedule->items()->create(['course_id' => $course->id]);

    visit(route('schedules.edit', $schedule))
        ->assertPresent('[data-testid="schedule-step-courses"]')
        ->assertChecked('[data-testid="course-checkbox-'.$course->id.'"]');
});

it('switches between the steps with the step radios', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create(['term' => '2025B', 'name' => '步驟切換課程']);

    visit(route('schedules.create', ['term' => '2025B']))
        ->assertPresent('[data-testid="schedule-step-2"][disabled]')
        ->assertSee('請選擇本學期課程（最多 14 門）。')
        ->click('[data-testid="course-checkbox-'.$course->id.'"]')
        ->click('[data-testid="schedule-step-label-2"]')
        ->assertPresent('[data-testid="selected-item-'.$course->id.'"]')
        ->click('[data-testid="schedule-step-label-1"]')
        ->assertChecked('[data-testid="course-checkbox-'.$course->id.'"]');
});

it('clears the filters when adding another course from the class step', function () {
    config()->set('app.current_semester', '2025B');

    $first = Course::factory()->create(['term' => '2025B', 'name' => '甲種測試課程']);
    $second = Course::factory()->create(['term' => '2025B', 'name' => '乙種測試課程']);

    visit(route('schedules.create', ['term' => '2025B']))
        ->fill('#search', '甲種')
        ->click('[data-testid="course-checkbox-'.$first->id.'"]')
        ->click('[data-testid="schedule-next"]')
        ->assertMissing('[data-testid="schedule-back"]:has-text("調整課程")')
        ->click('[data-testid="schedule-add-more"]')
        ->assertValue('#search', '')
        ->assertPresent('[data-testid="course-checkbox-'.$second->id.'"]')
        ->assertChecked('[data-testid="course-checkbox-'.$first->id.'"]');
});

it('asks whether to add to the existing schedule or start over', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create(['term' => '2025B', 'name' => '重新開始課程']);
    $schedule = StudentSchedule::factory()->create();

    $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]));

    visit(route('schedules.create', ['term' => '2025B']))
        ->assertPresent('[data-testid="schedule-existing-choice"]')
        ->assertMissing('[data-testid="course-checkbox-'.$course->id.'"]')
        ->click('[data-testid="schedule-choice-restart"]')
        ->assertMissing('[data-testid="schedule-existing-choice"]')
        ->assertPresent('[data-testid="course-checkbox-'.$course->id.'"]');
});
