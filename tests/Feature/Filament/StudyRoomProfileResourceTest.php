<?php

use App\Enums\UserRole;
use App\Events\StudyRoomUpdated;
use App\Filament\Resources\StudyRoomProfiles\Pages\ListStudyRoomProfiles;
use App\Filament\Resources\StudyRoomProfiles\StudyRoomProfileResource;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

function studyRoomScheduleCookiePayload(StudentSchedule $schedule): string
{
    return json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]);
}

test('the list renders for an admin', function () {
    $admin = User::factory()->createOne(['roles' => [UserRole::Admin->value]]);
    $schedule = StudentSchedule::factory()->create();
    $profile = StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '測試暱稱']);

    Livewire::actingAs($admin)
        ->test(ListStudyRoomProfiles::class)
        ->assertCanSeeTableRecords([$profile])
        ->assertSee('測試暱稱');
});

test('a non-admin cannot access the study room profile resource', function () {
    $user = User::factory()->createOne();

    $this->actingAs($user)
        ->get(StudyRoomProfileResource::getUrl())
        ->assertForbidden();
});

test('force-resetting a nickname clears the cooldown and lets the user set a new nickname immediately', function () {
    Event::fake([StudyRoomUpdated::class]);

    $admin = User::factory()->createOne(['roles' => [UserRole::Admin->value]]);
    $schedule = StudentSchedule::factory()->create();
    $profile = StudyRoomProfile::factory()->for($schedule, 'schedule')->create([
        'nickname' => '壞暱稱',
        'nickname_changed_at' => now(),
    ]);
    $seat = StudyRoomSeat::factory()->occupiedBy($schedule)->create();

    Livewire::actingAs($admin)
        ->test(ListStudyRoomProfiles::class)
        ->callAction(TestAction::make('forceResetNickname')->table($profile))
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    $profile->refresh();

    expect($profile->nickname)->toBeNull()
        ->and($profile->nickname_changed_at)->toBeNull()
        ->and($profile->nickname_reset_at)->not->toBeNull()
        ->and($profile->nickname_reset_by)->toBe($admin->id);

    Event::assertDispatched(StudyRoomUpdated::class, fn (StudyRoomUpdated $event): bool => $event->seat->is($seat));

    $response = $this->withCredentials()
        ->withCookie('student_schedule', studyRoomScheduleCookiePayload($schedule))
        ->post(route('study-room.profile.update'), [
            'nickname' => '新暱稱',
            'emoji' => config('study-room.emojis')[0],
            'playSoundOnTimerEnd' => true,
            'notifyOnTimerEnd' => false,
        ]);

    $response->assertSessionHasNoErrors();

    expect($profile->refresh()->nickname)->toBe('新暱稱');
});
