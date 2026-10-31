<?php

use App\Enums\StudyActivityVerb;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

$progressSession = function (StudentSchedule $schedule, string $endedAtTaipei, int $seconds, array $overrides = []): StudyRoomSession {
    $endedAt = Date::parse($endedAtTaipei, 'Asia/Taipei')->utc();

    return StudyRoomSession::factory()->for($schedule, 'schedule')->create([
        'started_at' => $endedAt->copy()->subSeconds($seconds),
        'ended_at' => $endedAt,
        'focus_seconds' => $seconds,
        ...$overrides,
    ]);
};

$viewerCookie = function (StudentSchedule $schedule): string {
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
};

beforeEach(function () {
    $this->travelTo(Date::parse('2026-09-11 10:00:00', 'Asia/Taipei')); // a Friday
});

it('sends visitors without a schedule back to the study room', function () {
    $this->get(route('study-room.progress'))->assertRedirect(route('study-room.show'));
});

it('defaults to the current month and week, marking dates with records', function () use ($progressSession, $viewerCookie) {
    $schedule = StudentSchedule::factory()->create();
    $progressSession($schedule, '2026-09-10 20:00:00', 1800);
    $progressSession($schedule, '2026-09-02 20:00:00', 600);
    $progressSession(StudentSchedule::factory()->create(), '2026-09-09 20:00:00', 900);

    $this->withCredentials()->withCookie('student_schedule', $viewerCookie($schedule))
        ->get(route('study-room.progress'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudyRoom/Progress')
            ->where('progress.month', '2026-09')
            ->where('progress.gridStart', '2026-08-31')
            ->where('progress.weekStart', '2026-09-07')
            ->where('progress.activeDates', ['2026-09-02', '2026-09-10'])
            ->where('progress.weekFocusSeconds', 1800)
            ->has('progress.days', 7)
            ->where('progress.days.3.date', '2026-09-10')
            ->where('progress.days.3.focusSeconds', 1800));
});

it('normalises any date to its Monday and shows that week', function () use ($progressSession, $viewerCookie) {
    $schedule = StudentSchedule::factory()->create();
    // Sunday 23:30 Taipei still belongs to the week that starts on Monday 08-31.
    $progressSession($schedule, '2026-09-06 23:30:00', 1200);
    $progressSession($schedule, '2026-09-07 00:30:00', 300);

    $this->withCredentials()->withCookie('student_schedule', $viewerCookie($schedule))
        ->get(route('study-room.progress', ['week' => '2026-09-03']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('progress.weekStart', '2026-08-31')
            ->where('progress.month', '2026-09')
            ->where('progress.weekFocusSeconds', 1200)
            ->where('progress.days.6.date', '2026-09-06'));
});

it('excludes 面授 by default and includes it on request', function () use ($progressSession, $viewerCookie) {
    $schedule = StudentSchedule::factory()->create();
    $progressSession($schedule, '2026-09-08 20:00:00', 600, ['activity_verb' => StudyActivityVerb::InPersonClass, 'subject_label' => '面授課']);
    $progressSession($schedule, '2026-09-09 20:00:00', 900, ['activity_verb' => null, 'subject_label' => '微積分']);

    $request = fn (array $query) => $this->withCredentials()->withCookie('student_schedule', $viewerCookie($schedule))
        ->get(route('study-room.progress', $query));

    $request([])->assertInertia(fn (Assert $page) => $page
        ->where('progress.activeDates', ['2026-09-09'])
        ->where('progress.weekFocusSeconds', 900)
        ->where('progress.includeInPerson', false)
        ->has('progress.subjects', 1));

    $request(['include_in_person' => '1'])->assertInertia(fn (Assert $page) => $page
        ->where('progress.activeDates', ['2026-09-08', '2026-09-09'])
        ->where('progress.weekFocusSeconds', 1500)
        ->where('progress.includeInPerson', true)
        ->where('progress.subjects.0.label', '微積分')
        ->where('progress.subjects.0.percent', 60));
});

it('groups subjects, falling back to 其他 and folding the tail', function () use ($progressSession, $viewerCookie) {
    $schedule = StudentSchedule::factory()->create();
    foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $i => $label) {
        $progressSession($schedule, '2026-09-08 20:00:00', 1000 - $i * 100, ['subject_label' => $label]);
    }
    $progressSession($schedule, '2026-09-08 21:00:00', 50, ['subject_label' => null]);

    $this->withCredentials()->withCookie('student_schedule', $viewerCookie($schedule))
        ->get(route('study-room.progress'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('progress.subjects', 6)
            ->where('progress.subjects.0.label', 'A')
            ->where('progress.subjects.5.label', '其他'));
});
