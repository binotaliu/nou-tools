<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Database\QueryException;

it('creates rows via all three study room factories', function () {
    $profile = StudyRoomProfile::factory()->create();
    $seat = StudyRoomSeat::factory()->create();
    $session = StudyRoomSession::factory()->create();

    expect($profile)->toBeInstanceOf(StudyRoomProfile::class)
        ->and($seat)->toBeInstanceOf(StudyRoomSeat::class)
        ->and($session)->toBeInstanceOf(StudyRoomSession::class);
});

it('allows two unoccupied seats to both hold a NULL student_schedule_id', function () {
    $first = StudyRoomSeat::factory()->create(['student_schedule_id' => null]);
    $second = StudyRoomSeat::factory()->create(['student_schedule_id' => null]);

    expect($first->student_schedule_id)->toBeNull()
        ->and($second->student_schedule_id)->toBeNull();

    expect(StudyRoomSeat::query()->whereNull('student_schedule_id')->count())->toBe(2);
});

it('prevents a student from holding two seats at once', function () {
    $schedule = StudentSchedule::factory()->create();

    StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    expect(fn () => StudyRoomSeat::factory()->occupiedBy($schedule)->create())
        ->toThrow(QueryException::class);
});
