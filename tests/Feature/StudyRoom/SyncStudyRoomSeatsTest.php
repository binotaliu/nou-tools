<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use NouTools\Domains\StudyRoom\Actions\SyncStudyRoomSeats;

it('creates 24 seats per floor with expected codes and labels', function () {
    $seatCount = app(SyncStudyRoomSeats::class)();

    $maxFloor = (int) config('study-room.floors.max');

    expect($seatCount)->toBe(24 * $maxFloor);
    expect(StudyRoomSeat::query()->count())->toBe(24 * $maxFloor);

    $soloSeat = StudyRoomSeat::query()->where('code', '1-S01')->first();
    expect($soloSeat)->not->toBeNull();
    expect($soloSeat->label)->toBe('1F 單人座 01');

    $tableSeat = StudyRoomSeat::query()->where('code', '1-T3-2')->first();
    expect($tableSeat)->not->toBeNull();
    expect($tableSeat->label)->toBe('1F 3 號桌 2 位');
    expect($tableSeat->group_code)->toBe('T3');
});

it('is a no-op when run twice', function () {
    app(SyncStudyRoomSeats::class)();
    $idsAfterFirstRun = StudyRoomSeat::query()->orderBy('id')->pluck('id');
    $countAfterFirstRun = StudyRoomSeat::query()->count();

    app(SyncStudyRoomSeats::class)();
    $idsAfterSecondRun = StudyRoomSeat::query()->orderBy('id')->pluck('id');
    $countAfterSecondRun = StudyRoomSeat::query()->count();

    expect($countAfterSecondRun)->toBe($countAfterFirstRun);
    expect($idsAfterSecondRun->all())->toBe($idsAfterFirstRun->all());
});

it('removes unoccupied seats orphaned by a shrunken config', function () {
    app(SyncStudyRoomSeats::class)();

    config()->set('study-room.layout.solo_seats_per_floor', 6);

    app(SyncStudyRoomSeats::class)();

    expect(StudyRoomSeat::query()->where('code', '1-S07')->exists())->toBeFalse();
    expect(StudyRoomSeat::query()->where('code', '1-S06')->exists())->toBeTrue();
});

it('keeps an occupied seat even when the config shrinks it away', function () {
    app(SyncStudyRoomSeats::class)();

    $schedule = StudentSchedule::factory()->create();
    StudyRoomSeat::query()->where('code', '1-S07')->update([
        'student_schedule_id' => $schedule->id,
        'occupied_at' => now(),
        'last_seen_at' => now(),
    ]);

    config()->set('study-room.layout.solo_seats_per_floor', 6);

    app(SyncStudyRoomSeats::class)();

    expect(StudyRoomSeat::query()->where('code', '1-S07')->exists())->toBeTrue();
});
