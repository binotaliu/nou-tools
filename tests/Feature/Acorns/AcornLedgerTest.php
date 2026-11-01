<?php

use App\Enums\AcornTransactionKind;
use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Models\AcornTransaction;
use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Actions\AwardLearningProgressAcorns;
use NouTools\Domains\Acorns\Actions\AwardStudyAcorns;
use NouTools\Domains\Acorns\Actions\BackfillAcorns;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;
use NouTools\Domains\Acorns\Actions\RefundAcorns;
use NouTools\Domains\Acorns\Actions\SpendAcorns;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\StudyRoom\Actions\RecordStudySession;

beforeEach(function () {
    // Wednesday noon in Taipei, so the week (Mon–Sun) has days on both sides.
    Date::setTestNow(Date::parse('2026-11-04 12:00:00', 'Asia/Taipei'));
});

$studySession = function (StudentSchedule $schedule, int $minutes, ?string $endedAt = null, ?StudyActivityVerb $verb = StudyActivityVerb::Review): StudyRoomSession {
    $end = Date::parse($endedAt ?? '2026-11-04 10:00:00', 'Asia/Taipei')->utc();

    return StudyRoomSession::factory()->create([
        'student_schedule_id' => $schedule->id,
        'activity_verb' => $verb,
        'started_at' => $end->copy()->subMinutes($minutes),
        'ended_at' => $end,
        'focus_seconds' => $minutes * 60,
    ]);
};

it('pays one acorn per ten focus minutes and ignores fractions', function () use ($studySession) {
    $schedule = StudentSchedule::factory()->create();
    $studySession($schedule, 25);
    $studySession($schedule, 25);

    $paid = app(AwardStudyAcorns::class)($schedule->id, Date::now());

    expect($paid)->toBe(5) // 50 minutes
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(5);
});

it('is idempotent and only pays the difference when more is studied', function () use ($studySession) {
    $schedule = StudentSchedule::factory()->create();
    $studySession($schedule, 60);
    $award = app(AwardStudyAcorns::class);

    expect($award($schedule->id, Date::now()))->toBe(6)
        ->and($award($schedule->id, Date::now()))->toBe(0);

    $studySession($schedule, 30);

    expect($award($schedule->id, Date::now()))->toBe(3)
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(9);
});

it('caps the day and the week', function () use ($studySession) {
    $schedule = StudentSchedule::factory()->create();
    $award = app(AwardStudyAcorns::class);

    // Monday to Wednesday: 4 hours each is capped at 12 a day.
    foreach (['2026-11-02', '2026-11-03', '2026-11-04'] as $day) {
        $studySession($schedule, 240, "{$day} 10:00:00");
        expect($award($schedule->id, Date::parse("{$day} 12:00:00", 'Asia/Taipei')))->toBe(12);
    }

    config()->set('cafe.acorns.study.weekly_cap', 40);
    $studySession($schedule, 240, '2026-11-05 10:00:00');

    // 36 earned so far this week; only 4 fit under the weekly cap of 40.
    expect($award($schedule->id, Date::parse('2026-11-05 12:00:00', 'Asia/Taipei')))->toBe(4);
});

it('buckets sessions by the Taipei day', function () use ($studySession) {
    $schedule = StudentSchedule::factory()->create();
    // 23:30 Taipei on the 3rd is 15:30 UTC on the 3rd; 00:30 Taipei on the 4th is 16:30 UTC on the 3rd.
    $studySession($schedule, 30, '2026-11-03 23:30:00');
    $studySession($schedule, 30, '2026-11-04 00:30:00');

    $award = app(AwardStudyAcorns::class);

    expect($award($schedule->id, Date::parse('2026-11-04 12:00:00', 'Asia/Taipei')))->toBe(3)
        ->and($award($schedule->id, Date::parse('2026-11-03 12:00:00', 'Asia/Taipei')))->toBe(3);
});

it('does not count in-person class time', function () use ($studySession) {
    $schedule = StudentSchedule::factory()->create();
    $studySession($schedule, 120, null, StudyActivityVerb::InPersonClass);
    $studySession($schedule, 20, null, null);

    expect(app(AwardStudyAcorns::class)($schedule->id, Date::now()))->toBe(2);
});

it('pays nothing for a day whose credits would already have expired', function () use ($studySession) {
    $schedule = StudentSchedule::factory()->create();
    $studySession($schedule, 120, '2026-09-01 10:00:00');

    expect(app(AwardStudyAcorns::class)($schedule->id, Date::parse('2026-09-01 12:00:00', 'Asia/Taipei')))->toBe(0);
});

it('awards from RecordStudySession when a focus segment is recorded', function () {
    $schedule = StudentSchedule::factory()->create();
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create([
        'timer_phase' => StudyTimerPhase::Focus,
        'timer_mode' => StudyTimerMode::Pomodoro,
        'timer_started_at' => Date::now()->subMinutes(45),
        'last_seen_at' => Date::now(),
    ]);

    app(RecordStudySession::class)($seat);

    expect(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(4);
});

it('excludes expired credits from the balance and spends the soonest-expiring first', function () {
    $schedule = StudentSchedule::factory()->create();
    AcornTransaction::factory()->for($schedule, 'schedule')->expired()->create();
    $late = AcornTransaction::factory()->for($schedule, 'schedule')->credit(4, 20)->create();
    $soon = AcornTransaction::factory()->for($schedule, 'schedule')->credit(4, 5)->create();

    expect(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(8);

    $spend = app(SpendAcorns::class)($schedule->id, 6, AcornTransactionKind::CoffeePurchase);

    expect($spend->amount)->toBe(-6)
        ->and($soon->fresh()->remaining)->toBe(0)
        ->and($late->fresh()->remaining)->toBe(2)
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(2)
        ->and(app(ReadAcornBalance::class)($schedule->id)->nextExpiryAmount)->toBe(2);
});

it('refuses to spend more than the live balance', function () {
    $schedule = StudentSchedule::factory()->create();
    AcornTransaction::factory()->for($schedule, 'schedule')->credit(2)->create();
    AcornTransaction::factory()->for($schedule, 'schedule')->expired()->create(['remaining' => 50, 'amount' => 50]);

    expect(fn () => app(SpendAcorns::class)($schedule->id, 3, AcornTransactionKind::CoffeePurchase))
        ->toThrow(InsufficientAcornsException::class)
        ->and(AcornTransaction::query()->where('amount', '<', 0)->count())->toBe(0);
});

it('refunds a spend as a fresh credit', function () {
    $schedule = StudentSchedule::factory()->create();
    AcornTransaction::factory()->for($schedule, 'schedule')->credit(5, 2)->create();
    $spend = app(SpendAcorns::class)($schedule->id, 5, AcornTransactionKind::StickyNotePurchase);

    $refund = app(RefundAcorns::class)($spend);

    expect($refund->amount)->toBe(5)
        ->and($refund->kind)->toBe(AcornTransactionKind::Refund)
        ->and($refund->expires_at->isAfter(Date::now()->addDays(29)))->toBeTrue()
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(5);
});

$progressBlobs = function (array $video = [], array $homework = []): array {
    $progress = [];
    foreach ($video as $week => $ticked) {
        $progress['1'][(string) $week] = ['video' => $ticked, 'textbook' => false];
    }

    return ['progress' => $progress, 'homework' => $homework];
};

it('pays learning progress once per item, ever', function () use ($progressBlobs) {
    $schedule = StudentSchedule::factory()->create();
    $award = app(AwardLearningProgressAcorns::class);
    $empty = $progressBlobs();

    expect($award($schedule->id, '2025B', $empty, $progressBlobs([1 => true, 2 => true])))->toBe(['awarded' => 2, 'capped' => false])
        // Un-tick then tick again: nothing.
        ->and($award($schedule->id, '2025B', $progressBlobs([1 => false]), $progressBlobs([1 => true])))->toBe(['awarded' => 0, 'capped' => false])
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(2);
});

it('pays homework double and respects the daily cap but still claims every item', function () use ($progressBlobs) {
    $schedule = StudentSchedule::factory()->create();
    $award = app(AwardLearningProgressAcorns::class);

    $weeks = array_fill_keys(range(1, 6), true);
    $homework = ['1' => ['1' => ['completed' => '1'], '2' => ['completed' => true]]];

    // 6 weeks (6) + 2 homework (4) = 10, capped to 6.
    $result = $award($schedule->id, '2025B', $progressBlobs(), $progressBlobs($weeks, $homework));

    expect($result)->toBe(['awarded' => 6, 'capped' => true]);

    // Tomorrow the same items still pay nothing.
    Date::setTestNow(Date::now()->addDay());
    $again = $award($schedule->id, '2025B', $progressBlobs(), $progressBlobs($weeks, $homework));

    expect($again)->toBe(['awarded' => 0, 'capped' => false]);
});

it('awards on a learning progress save and flashes the result', function () {
    $schedule = StudentSchedule::factory()->create();
    LearningProgress::factory()->create(['student_schedule_id' => $schedule->id, 'term' => '2025B']);

    $this->put(route('learning-progress.update', ['schedule' => $schedule, 'term' => '2025B']), [
        'progress' => ['1' => ['1' => ['video' => true, 'textbook' => true]]],
        'notes' => [],
        'homework' => ['1' => ['1' => ['completed' => true, 'deadline' => null, 'note' => '']]],
    ])->assertRedirect()->assertSessionHas('success', '學習進度已更新，獲得 4 顆橡實');

    expect(app(ReadAcornBalance::class)($schedule->id)->learningToday)->toBe(4);
});

it('backfills recent study and a capped welcome gift, and can run twice', function () use ($studySession, $progressBlobs) {
    $schedule = StudentSchedule::factory()->create();
    $studySession($schedule, 60, '2026-11-03 10:00:00');
    $studySession($schedule, 60, '2026-11-04 10:00:00');
    $studySession($schedule, 600, '2026-08-01 10:00:00'); // long expired

    $weeks = array_fill_keys(range(1, 30), true);
    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
        'progress' => $progressBlobs($weeks)['progress'],
    ]);

    $first = app(BackfillAcorns::class)();

    expect($first)->toBe(['schedules' => 1, 'study' => 12, 'welcome' => 20])
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(32);

    $second = app(BackfillAcorns::class)();

    expect($second)->toBe(['schedules' => 1, 'study' => 0, 'welcome' => 0])
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(32);
});

it('serves the balance to the signed-in schedule only', function () {
    $schedule = StudentSchedule::factory()->create();
    AcornTransaction::factory()->for($schedule, 'schedule')->credit(7)->create();

    $this->getJson(route('acorns.show'))->assertForbidden();

    $cookie = json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->getJson(route('acorns.show'))
        ->assertOk()
        ->assertJsonPath('acorns.balance', 7)
        ->assertJsonPath('acorns.recent.0.amount', 7);
});
