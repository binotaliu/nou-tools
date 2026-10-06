<?php

use App\Models\Course;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use Illuminate\Support\Str;

$createGoalScheduleWithProfile = function (): StudentSchedule {
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Browser Study Goal Schedule',
    ]);

    StudentScheduleItem::query()->create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
    ]);
    StudyRoomProfile::factory()->create([
        'student_schedule_id' => $schedule->id,
        'nickname' => '目標達人',
    ]);

    return $schedule;
};

it('sets weekly and weekday goals from the 目標 modal and shows the progress on the wall', function () use ($createGoalScheduleWithProfile) {
    $schedule = $createGoalScheduleWithProfile();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    $page->navigate(route('study-room.show'));

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-root"]\') !== null');

    $page->assertMissing('[data-testid="study-room-goal-progress"]')
        ->click('[data-testid="study-room-personal-info-goal"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-goal-form"]\') !== null');

    // Every weekday gets a goal so "today" is covered whatever day the test runs.
    $page->fill('[data-testid="study-room-goal-weekly"]', '600');

    foreach (range(1, 7) as $weekday) {
        $page->fill("[data-testid=\"study-room-goal-minutes-{$weekday}\"]", '90');
    }

    $page->fill('[data-testid="study-room-goal-remind-1"]', '20:00')
        ->assertVisible('[data-testid="study-room-goal-remind-clear-1"]')
        ->click('[data-testid="study-room-goal-remind-clear-1"]')
        ->assertMissing('[data-testid="study-room-goal-remind-clear-1"]');

    $page->click('[data-testid="study-room-goal-submit"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-goal-form"]\').offsetParent === null');

    $page->assertSeeIn('[data-testid="study-room-goal-progress"]', '0/90 分鐘')
        ->assertSeeIn('[data-testid="study-room-goal-progress"]', '0/600 分鐘');

    expect($schedule->studyRoomProfile->fresh())->weekly_goal_minutes->toBe(600);
});
