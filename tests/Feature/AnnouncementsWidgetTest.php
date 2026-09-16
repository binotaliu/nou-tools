<?php

use App\Models\Announcement;
use App\Models\StudentSchedule;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

it('shows the latest announcements matching the schedule selection', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Widget Test',
        'announcement_categories' => [
            '教務處' => ['考試資訊'],
        ],
    ]);

    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '符合條件的公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '學務處',
        'category' => '活動資訊',
        'title' => '不符合條件的公告',
    ]);

    $response = get(route('schedules.show', $schedule));

    // The widget's copy ("最新公告"/"檢視更多公告") is static Vue template
    // text in AnnouncementsWidget.vue; the filtered announcement list is
    // server-computed into the `announcementsWidget` prop by
    // BuildScheduleAnnouncementsWidget, so assert against that instead.
    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $page->component('Schedule/Show');

        $titles = collect($page->toArray()['props']['announcementsWidget']['announcements'])->pluck('title');

        expect($titles)->toContain('符合條件的公告');
        expect($titles)->not->toContain('不符合條件的公告');
    });
});

it('shows the choose-categories empty state when announcement_categories is explicitly empty', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Empty Selection',
        'announcement_categories' => [],
    ]);

    $response = get(route('schedules.show', $schedule));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Schedule/Show')
            ->where('announcementsWidget.hasAnySelection', false)
    );
});

it('defaults to showing 各處室 announcements when announcement_categories is null', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Default Selection',
    ]);

    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '教務處預設公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '人文學系',
        'category' => '課程資訊',
        'title' => '學系不應顯示的公告',
    ]);

    $response = get(route('schedules.show', $schedule));

    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $page->component('Schedule/Show');

        $titles = collect($page->toArray()['props']['announcementsWidget']['announcements'])->pluck('title');

        expect($titles)->toContain('教務處預設公告');
        expect($titles)->not->toContain('學系不應顯示的公告');
    });
});

it('links "see more" to the announcements index pre-filtered to the same selection', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'See More Link',
        'announcement_categories' => [
            '教務處' => ['考試資訊'],
        ],
    ]);

    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '考試資訊',
        'title' => '保留的公告',
    ]);

    Announcement::factory()->create([
        'source_name' => '教務處',
        'category' => '註冊選課',
        'title' => '不應出現的公告',
    ]);

    $seeMoreUrl = route('announcements.index', [
        'source_categories' => ['教務處' => ['考試資訊']],
    ]);

    $response = get(route('schedules.show', $schedule));
    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Schedule/Show')
            ->where('announcementsWidget.moreAnnouncementsUrl', $seeMoreUrl)
    );

    // The announcements index now renders through Inertia (see
    // AnnouncementController), so its titles no longer appear in the raw
    // HTML response body — assert against the Inertia `viewModel` prop
    // instead.
    $indexResponse = get($seeMoreUrl);
    $indexResponse->assertSuccessful();
    $indexResponse->assertInertia(function (Assert $page) {
        $titles = collect($page->toArray()['props']['viewModel']['announcements']['data'])->pluck('title');

        expect($titles)->toContain('保留的公告');
        expect($titles)->not->toContain('不應出現的公告');
    });
});

it('hides the announcements widget when show_announcements display option is off', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Toggled Off',
        'display_options' => ['show_announcements' => false],
    ]);

    $response = get(route('schedules.show', $schedule));

    // The widget itself is still rendered (it's an independent prop from
    // display_options), but wrapped in `v-if="displayOptions.showAnnouncements"`
    // in Show.vue, so assert the flag that gates it.
    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page->component('Schedule/Show')
            ->where('viewModel.displayOptions.show_announcements', false)
    );
});
