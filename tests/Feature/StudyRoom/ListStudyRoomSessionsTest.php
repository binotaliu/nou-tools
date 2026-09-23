<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomSession;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\ListStudyRoomSessions;

$studyRoomCookieForSchedule = function (StudentSchedule $schedule): string {
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
};

it('only returns the viewer\'s own sessions, newest first', function () {
    $schedule = StudentSchedule::factory()->create();
    $other = StudentSchedule::factory()->create();

    $older = StudyRoomSession::factory()->for($schedule, 'schedule')->create(['started_at' => now()->subHours(2)]);
    $newer = StudyRoomSession::factory()->for($schedule, 'schedule')->create(['started_at' => now()->subMinutes(10)]);
    StudyRoomSession::factory()->for($other, 'schedule')->create();

    $viewer = StudentScheduleCookie::fromModel($schedule);

    $sessions = app(ListStudyRoomSessions::class)($viewer);

    expect($sessions)->toHaveCount(2)
        ->and($sessions[0]->focusSeconds)->toBe($newer->focus_seconds)
        ->and($sessions[1]->focusSeconds)->toBe($older->focus_seconds);
});

it('respects the limit', function () {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomSession::factory()->for($schedule, 'schedule')->count(5)->create();

    $viewer = StudentScheduleCookie::fromModel($schedule);

    $sessions = app(ListStudyRoomSessions::class)($viewer, limit: 3);

    expect($sessions)->toHaveCount(3);
});

it('returns sessions as JSON for a viewer with a schedule cookie', function () use ($studyRoomCookieForSchedule) {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomSession::factory()->for($schedule, 'schedule')->create();

    $response = $this->withCredentials()
        ->withCookie('student_schedule', $studyRoomCookieForSchedule($schedule))
        ->getJson(route('study-room.sessions'));

    $response->assertOk()->assertJsonStructure([
        'sessions' => [
            '*' => ['activityLabel', 'subjectLabel', 'startedAt', 'endedAt', 'focusSeconds', 'wasCompleted'],
        ],
    ]);
});

it('rejects a request with no schedule cookie', function () {
    $response = $this->getJson(route('study-room.sessions'));

    $response->assertStatus(403);
});
