<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\ListRecentStudyRoomSessionsForStats;

it('rejects a request with no schedule cookie', function () {
    $response = $this->getJson(route('study-room.sessions.stats'));

    $response->assertStatus(403);
});

it('returns 7 days, oldest first, zero-filled where the viewer had no sessions', function () {
    $this->travelTo(Date::parse('2026-09-11 10:00:00', 'Asia/Taipei'));

    $schedule = StudentSchedule::factory()->create();

    StudyRoomSession::factory()->for($schedule, 'schedule')->create([
        'started_at' => Date::parse('2026-09-11 09:00:00', 'Asia/Taipei'),
        'ended_at' => Date::parse('2026-09-11 09:25:00', 'Asia/Taipei'),
        'focus_seconds' => 1500,
    ]);

    StudyRoomSession::factory()->for($schedule, 'schedule')->create([
        'started_at' => Date::parse('2026-09-09 09:00:00', 'Asia/Taipei'),
        'ended_at' => Date::parse('2026-09-09 09:50:00', 'Asia/Taipei'),
        'focus_seconds' => 3000,
    ]);

    // Outside the 7-day window — must not be counted.
    StudyRoomSession::factory()->for($schedule, 'schedule')->create([
        'started_at' => Date::parse('2026-09-01 09:00:00', 'Asia/Taipei'),
        'ended_at' => Date::parse('2026-09-01 09:25:00', 'Asia/Taipei'),
        'focus_seconds' => 1500,
    ]);

    $response = $this->withCredentials()
        ->withCookie('student_schedule', json_encode([
            'id' => $schedule->id,
            'uuid' => $schedule->uuid,
            'name' => $schedule->name,
        ]))
        ->getJson(route('study-room.sessions.stats'));

    $response->assertOk();

    $days = $response->json('days');

    expect($days)->toHaveCount(7)
        ->and($days[0]['date'])->toBe('2026-09-05')
        ->and($days[6]['date'])->toBe('2026-09-11')
        ->and($days[6]['label'])->toBe('今天')
        ->and($days[6]['focusSeconds'])->toBe(1500)
        ->and($days[6]['sessions'])->toHaveCount(1)
        ->and($days[4]['date'])->toBe('2026-09-09')
        ->and($days[4]['focusSeconds'])->toBe(3000)
        ->and($days[0]['focusSeconds'])->toBe(0)
        ->and($days[0]['sessions'])->toHaveCount(0);

    $totalFocusSeconds = array_sum(array_column($days, 'focusSeconds'));
    expect($totalFocusSeconds)->toBe(4500);
});

it('only includes the viewer\'s own sessions', function () {
    $schedule = StudentSchedule::factory()->create();
    $other = StudentSchedule::factory()->create();

    StudyRoomSession::factory()->for($schedule, 'schedule')->create(['started_at' => now(), 'ended_at' => now()]);
    StudyRoomSession::factory()->for($other, 'schedule')->create(['started_at' => now(), 'ended_at' => now()]);

    $viewer = StudentScheduleCookie::fromModel($schedule);

    $sessions = app(ListRecentStudyRoomSessionsForStats::class)($viewer);

    expect($sessions)->toHaveCount(1);
});
