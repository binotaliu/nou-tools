<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
});

it('renders the settings page', function () {
    get(route('settings'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Settings/Show'));
});

it('has no notification settings for a visitor without a remembered schedule', function () {
    get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications', null)
            ->has('vapidPublicKey'));
});

it('reports both notification opt-ins for the remembered schedule', function () {
    $schedule = StudentSchedule::factory()->create();
    $schedule->notify_on_class_start = true;
    $schedule->save();
    StudyRoomProfile::factory()->create([
        'student_schedule_id' => $schedule->id,
        'notify_on_timer_end' => true,
    ]);

    $this->withCookie('student_schedule', json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid]))
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.scheduleToken', $schedule->getRouteKey())
            ->where('notifications.classReminders', true)
            ->where('notifications.hasStudyRoomProfile', true)
            ->where('notifications.timerEnd', true));
});

it('marks the timer-end notification unavailable until a study room profile exists', function () {
    $schedule = StudentSchedule::factory()->create();

    $this->withCookie('student_schedule', json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid]))
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.classReminders', false)
            ->where('notifications.hasStudyRoomProfile', false)
            ->where('notifications.timerEnd', false));
});

it('server-renders its head tags and keeps the page out of search results', function () {
    get(route('settings'))
        ->assertSuccessful()
        ->assertSee('<title>設定 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo name="robots" content="noindex, nofollow" />', false);
});

it('is not listed in the sitemap', function () {
    get(route('sitemap'))
        ->assertSuccessful()
        ->assertDontSee(route('settings'), false);
});

it('carries the remembered schedule\'s backup link for the settings page', function () {
    $schedule = StudentSchedule::factory()->create(['name' => '備份測試']);

    $this->withCookie('student_schedule', json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid]))
        ->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.backup.name', '備份測試')
            ->where('notifications.backup.url', route('schedules.show', $schedule))
            ->where('notifications.backup.qrCodeSvg', fn (string $svg) => str_contains($svg, 'viewBox')));
});
