<?php

use App\Enums\UserRole;
use App\Filament\Pages\ManageStudyRoom;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

test('an admin can load the study room settings page', function () {
    $user = User::factory()->createOne(['roles' => [UserRole::Admin->value]]);

    $this->actingAs($user)
        ->get(ManageStudyRoom::getUrl())
        ->assertSuccessful()
        ->assertSee('自習室設定');
});

test('saving the study room settings page persists and renders the announcement', function () {
    $user = User::factory()->createOne(['roles' => [UserRole::Admin->value]]);

    Livewire::actingAs($user)
        ->test(ManageStudyRoom::class)
        ->fillForm([
            'announcement' => '**重要公告：期末考將至**',
            'forbiddenNicknames' => ['壞暱稱'],
            'isOpen' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $this->withCredentials()
        ->withCookie('student_schedule', json_encode([
            'id' => $schedule->id,
            'uuid' => $schedule->uuid,
            'name' => $schedule->name,
        ]))
        ->get(route('study-room.show'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page->component('StudyRoom/Show')
                ->where('announcementHtml', fn (string $html): bool => str_contains($html, '<strong>'))
        );
});

test('a non-admin cannot access the study room settings page', function () {
    $user = User::factory()->createOne();

    $this->actingAs($user)
        ->get(ManageStudyRoom::getUrl())
        ->assertForbidden();
});
