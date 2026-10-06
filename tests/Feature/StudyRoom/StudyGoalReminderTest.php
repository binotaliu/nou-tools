<?php

use App\Enums\StudyActivityVerb;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSession;
use App\Notifications\StudyGoalReminder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use NouTools\Domains\StudyRoom\Actions\SendStudyGoalReminders;

beforeEach(function () {
    Notification::fake();
    // Wednesday 20:03 in Taipei
    $this->travelTo(Date::parse('2026-09-16 20:03:00', 'Asia/Taipei'));
});

$goalReminderStudent = function (array $profile = [], bool $subscribed = true): StudentSchedule {
    $schedule = StudentSchedule::factory()->create();

    StudyRoomProfile::factory()->for($schedule, 'schedule')->create([
        'notify_on_goal_reminder' => true,
        'daily_goals' => [3 => ['minutes' => 60, 'remindAt' => '20:00']],
        ...$profile,
    ]);

    if ($subscribed) {
        $schedule->updatePushSubscription(
            endpoint: 'https://fcm.googleapis.com/fcm/send/'.$schedule->id,
            key: 'p256dh-key',
            token: 'auth-token',
        );
    }

    return $schedule;
};

$focusedToday = function (StudentSchedule $schedule, int $seconds): void {
    StudyRoomSession::factory()->for($schedule, 'schedule')->create([
        'started_at' => Date::now()->subHours(3),
        'ended_at' => Date::now()->subHours(2),
        'focus_seconds' => $seconds,
    ]);
};

it('reminds at the weekday time with what is left of today\'s goal', function () use ($goalReminderStudent, $focusedToday) {
    $schedule = $goalReminderStudent();
    $focusedToday($schedule, 20 * 60);

    expect(app(SendStudyGoalReminders::class)())->toBe(1);

    Notification::assertSentTo($schedule, StudyGoalReminder::class);
    expect($schedule->studyRoomProfile->fresh()->goal_reminder_sent_on->toDateString())->toBe('2026-09-16');
});

it('sends at most one reminder a day', function () use ($goalReminderStudent) {
    $goalReminderStudent();

    expect(app(SendStudyGoalReminders::class)())->toBe(1);
    $this->travelTo(Date::now()->addMinutes(2));
    expect(app(SendStudyGoalReminders::class)())->toBe(0);
});

it('stays quiet when today\'s goal is met', function () use ($goalReminderStudent, $focusedToday) {
    $schedule = $goalReminderStudent();
    $focusedToday($schedule, 61 * 60);

    expect(app(SendStudyGoalReminders::class)())->toBe(0);
    Notification::assertNothingSent();
});

it('falls back to the weekly goal when the weekday has no daily goal', function () use ($goalReminderStudent, $focusedToday) {
    $unmet = $goalReminderStudent([
        'weekly_goal_minutes' => 300,
        'daily_goals' => [3 => ['minutes' => null, 'remindAt' => '20:00']],
    ]);
    $met = $goalReminderStudent([
        'weekly_goal_minutes' => 100,
        'daily_goals' => [3 => ['minutes' => null, 'remindAt' => '20:00']],
    ]);
    $focusedToday($met, 100 * 60);

    expect(app(SendStudyGoalReminders::class)())->toBe(1);

    Notification::assertSentTo($unmet, StudyGoalReminder::class);
    Notification::assertNotSentTo($met, StudyGoalReminder::class);
});

it('sends a plain reminder when a time is set without any goal', function () use ($goalReminderStudent) {
    $schedule = $goalReminderStudent(['daily_goals' => [3 => ['minutes' => null, 'remindAt' => '20:00']]]);

    expect(app(SendStudyGoalReminders::class)())->toBe(1);
    Notification::assertSentTo($schedule, StudyGoalReminder::class);
});

it('does not remind before the time, long after it, or on another weekday', function (string $remindAt, int $weekday) use ($goalReminderStudent) {
    $goalReminderStudent(['daily_goals' => [$weekday => ['minutes' => 60, 'remindAt' => $remindAt]]]);

    expect(app(SendStudyGoalReminders::class)())->toBe(0);
})->with([
    'later today' => ['20:30', 3],
    'a quarter hour ago' => ['19:45', 3],
    'another weekday' => ['20:00', 4],
]);

it('skips students who opted out or have no push subscription', function () use ($goalReminderStudent) {
    $goalReminderStudent(['notify_on_goal_reminder' => false]);
    $goalReminderStudent(subscribed: false);

    expect(app(SendStudyGoalReminders::class)())->toBe(0);
    Notification::assertNothingSent();
});

it('does not count in-person class time towards a met goal unless the student opts in', function (bool $excludes, int $expectedSent) use ($goalReminderStudent) {
    $schedule = $goalReminderStudent(['goal_excludes_in_person_class' => $excludes]);

    StudyRoomSession::factory()->for($schedule, 'schedule')->create([
        'activity_verb' => StudyActivityVerb::InPersonClass,
        'started_at' => Date::now()->subHours(4),
        'ended_at' => Date::now()->subHours(2),
        'focus_seconds' => 61 * 60,
    ]);

    expect(app(SendStudyGoalReminders::class)())->toBe($expectedSent);
})->with([
    'excluded by default' => [true, 1],
    'opted in' => [false, 0],
]);
