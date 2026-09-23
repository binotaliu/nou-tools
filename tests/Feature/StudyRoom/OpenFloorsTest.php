<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use NouTools\Domains\StudyRoom\Actions\ResolveOpenFloorCount;
use NouTools\Domains\StudyRoom\Actions\SyncStudyRoomSeats;

$occupySeat = function (int $floor, int $seatNumber): void {
    $schedule = StudentSchedule::factory()->create();

    StudyRoomSeat::query()
        ->where('floor', $floor)
        ->where('kind', 'solo')
        ->where('seat_number', $seatNumber)
        ->update([
            'student_schedule_id' => $schedule->id,
            'occupied_at' => now(),
            'last_seen_at' => now(),
        ]);
};

$fillFloorCompletely = function (int $floor): void {
    StudyRoomSeat::query()->where('floor', $floor)->get()->each(function (StudyRoomSeat $seat) {
        $schedule = StudentSchedule::factory()->create();
        $seat->update([
            'student_schedule_id' => $schedule->id,
            'occupied_at' => now(),
            'last_seen_at' => now(),
        ]);
    });
};

beforeEach(function () {
    app(SyncStudyRoomSeats::class)();
});

it('opens floor 1 when the room is empty', function () {
    expect(app(ResolveOpenFloorCount::class)())->toBe(1);
});

it('keeps only floor 1 open while it has spare seats', function () use ($occupySeat) {
    $occupySeat(1, 1);
    $occupySeat(1, 2);

    expect(app(ResolveOpenFloorCount::class)())->toBe(1);
});

it('opens floor 2 once floor 1 is completely full', function () use ($occupySeat) {
    $soloSeats = (int) config('study-room.layout.solo_seats_per_floor');
    $tablesPerFloor = (int) config('study-room.layout.tables_per_floor');
    $seatsPerTable = (int) config('study-room.layout.seats_per_table');

    for ($seatNumber = 1; $seatNumber <= $soloSeats; $seatNumber++) {
        $occupySeat(1, $seatNumber);
    }

    for ($table = 1; $table <= $tablesPerFloor; $table++) {
        for ($seatNumber = 1; $seatNumber <= $seatsPerTable; $seatNumber++) {
            $schedule = StudentSchedule::factory()->create();
            StudyRoomSeat::query()
                ->where('floor', 1)
                ->where('kind', 'shared')
                ->where('group_code', 'T'.$table)
                ->where('seat_number', $seatNumber)
                ->update([
                    'student_schedule_id' => $schedule->id,
                    'occupied_at' => now(),
                    'last_seen_at' => now(),
                ]);
        }
    }

    expect(app(ResolveOpenFloorCount::class)())->toBe(2);
});

it('opens floor 3 once floors 1 and 2 are both completely full', function () use ($fillFloorCompletely) {
    $fillFloorCompletely(1);
    $fillFloorCompletely(2);

    expect(app(ResolveOpenFloorCount::class)())->toBe(3);
});

it('keeps floor 2 open when someone is alone there even though floor 1 has space', function () use ($occupySeat) {
    $occupySeat(2, 1);

    expect(app(ResolveOpenFloorCount::class)())->toBe(2);
});

it('closes floor 2 again once it empties while floor 1 has space', function () {
    $schedule = StudentSchedule::factory()->create();

    $seat = StudyRoomSeat::query()->where('floor', 2)->where('seat_number', 1)->where('kind', 'solo')->first();
    $seat->update([
        'student_schedule_id' => $schedule->id,
        'occupied_at' => now(),
        'last_seen_at' => now(),
    ]);

    expect(app(ResolveOpenFloorCount::class)())->toBe(2);

    $seat->update([
        'student_schedule_id' => null,
        'occupied_at' => null,
        'last_seen_at' => null,
    ]);

    expect(app(ResolveOpenFloorCount::class)())->toBe(1);
});

it('respects the floors.max cap even when every floor is completely full', function () use ($fillFloorCompletely) {
    config()->set('study-room.floors.max', 2);
    app(SyncStudyRoomSeats::class)();

    $fillFloorCompletely(1);
    $fillFloorCompletely(2);

    expect(app(ResolveOpenFloorCount::class)())->toBe(2);
});
