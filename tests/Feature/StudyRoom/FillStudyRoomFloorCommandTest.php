<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use NouTools\Domains\StudyRoom\Actions\ResolveOpenFloorCount;
use NouTools\Domains\StudyRoom\Actions\SyncStudyRoomSeats;

beforeEach(function () {
    config(['study-room.floors.max' => 3]);
    app(SyncStudyRoomSeats::class)();
});

it('fills every seat on the floor with test students so the next floor opens', function () {
    expect(app(ResolveOpenFloorCount::class)())->toBe(1);

    $seatsOnFloor = StudyRoomSeat::query()->where('floor', 1)->count();

    $this->artisan('study-room:fill-floor', ['floor' => 1])
        ->expectsOutputToContain("已在 1 樓塞入 {$seatsOnFloor} 位測試同學")
        ->assertSuccessful();

    expect(StudyRoomSeat::query()->where('floor', 1)->whereNull('student_schedule_id')->count())->toBe(0)
        ->and(app(ResolveOpenFloorCount::class)())->toBe(2)
        ->and(StudyRoomSeat::query()->where('floor', 1)->whereNotNull('timer_ends_at')->count())->toBeGreaterThan(0);
});

it('releases the test students and their throwaway schedules again', function () {
    $this->artisan('study-room:fill-floor', ['floor' => 1])->assertSuccessful();

    $seatsOnFloor = StudyRoomSeat::query()->where('floor', 1)->count();

    $this->artisan('study-room:fill-floor', ['--release' => true])
        ->expectsOutputToContain("已釋放 {$seatsOnFloor} 個測試同學的座位")
        ->assertSuccessful();

    expect(StudyRoomSeat::query()->whereNotNull('student_schedule_id')->count())->toBe(0)
        ->and(StudentSchedule::query()->count())->toBe(0)
        ->and(app(ResolveOpenFloorCount::class)())->toBe(1);
});

it('leaves real students alone when releasing', function () {
    $schedule = StudentSchedule::factory()->create(['name' => '真的同學']);
    $seat = StudyRoomSeat::query()->where('floor', 1)->first();
    $seat->student_schedule_id = $schedule->id;
    $seat->saveOrFail();

    $this->artisan('study-room:fill-floor', ['floor' => 1])->assertSuccessful();
    $this->artisan('study-room:fill-floor', ['--release' => true])->assertSuccessful();

    expect($seat->fresh()->student_schedule_id)->toBe($schedule->id)
        ->and(StudentSchedule::query()->count())->toBe(1);
});

it('refuses floors outside the configured range', function () {
    $this->artisan('study-room:fill-floor', ['floor' => 9])
        ->expectsOutput('樓層超出範圍。')
        ->assertFailed();
});
