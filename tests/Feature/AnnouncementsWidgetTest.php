<?php

use App\Models\Announcement;
use App\Models\StudentSchedule;
use Illuminate\Support\Str;

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

    $response->assertSuccessful();
    $response->assertSee('最新公告');
    $response->assertSee('符合條件的公告');
    $response->assertDontSee('不符合條件的公告');
    $response->assertSee('檢視更多公告');
});

it('shows the choose-categories empty state when announcement_categories is explicitly empty', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Empty Selection',
        'announcement_categories' => [],
    ]);

    $response = get(route('schedules.show', $schedule));

    $response->assertSuccessful();
    $response->assertSee('尚未選擇任何公告分類');
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
    $response->assertSee('教務處預設公告');
    $response->assertDontSee('學系不應顯示的公告');
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
    $response->assertSee($seeMoreUrl, false);

    $indexResponse = get($seeMoreUrl);
    $indexResponse->assertSuccessful();
    $indexResponse->assertSee('保留的公告');
    $indexResponse->assertDontSee('不應出現的公告');
});

it('hides the announcements widget when show_announcements display option is off', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Toggled Off',
        'display_options' => ['show_announcements' => false],
    ]);

    $response = get(route('schedules.show', $schedule));

    $response->assertSuccessful();
    $response->assertDontSee('最新公告');
});
