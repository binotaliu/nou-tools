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
        ->fill('#course-search', '瀏覽器測試')
        ->click('[data-testid="course-option-'.$course->id.'"]')
        ->fill('#schedule-name', 'Inertia Editor Schedule')
        ->click('[data-testid="schedule-submit"]')
        ->assertSee('課表已保存！')
        ->assertSee('瀏覽器測試課程');

    $schedule = StudentSchedule::where('name', 'Inertia Editor Schedule')->firstOrFail();

    expect($schedule->items()->pluck('course_id')->all())->toBe([$course->id]);
});
