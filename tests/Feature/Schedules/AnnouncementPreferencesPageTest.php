<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

it('renders the grouped announcement source catalog', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Preferences Test',
    ]);

    $response = get(route('schedules.customize', $schedule));

    // The group/source labels are rendered client-side (Customize.vue's
    // announcement-preferences tab) from `announcementPreferences.sourceGroups`,
    // so verify the catalog data itself.
    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) {
        $page->component('Schedule/Customize');

        $sourceGroups = $page->toArray()['props']['announcementPreferences']['sourceGroups'];
        $groupLabels = collect($sourceGroups)->pluck('groupLabel');
        $sourceNames = collect($sourceGroups)
            ->flatMap(fn (array $group) => collect($group['sources'])->pluck('source'));

        expect($groupLabels)->toContain('各處室');
        expect($groupLabels)->toContain('學系');
        expect($groupLabels)->toContain('學習指導中心');
        expect($sourceNames)->toContain('學校首頁');
        expect($sourceNames)->toContain('通識博雅教育中心');
        expect($sourceNames)->toContain('海外學生服務組');
    });
});

it('defaults to all 各處室 sources selected when announcement_categories is null', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Null Preferences',
    ]);

    expect($schedule->announcement_categories)->toBeNull();

    $administrativeCategories = collect(config('announcements.sources'))
        ->filter(fn (array $source): bool => ($source['is_active'] ?? false) && ($source['name'] ?? null) === '教務處')
        ->pluck('category')
        ->unique();

    expect($administrativeCategories)->not->toBeEmpty();

    $response = get(route('schedules.customize', $schedule));

    // The selected-category checkboxes are rendered client-side from
    // `announcementPreferences.sourceGroups[].sources[].selectedCategories`,
    // so verify that the 教務處 source is selected with all of its categories.
    $response->assertSuccessful();
    $response->assertInertia(function (Assert $page) use ($administrativeCategories) {
        $page->component('Schedule/Customize');

        $sourceGroups = $page->toArray()['props']['announcementPreferences']['sourceGroups'];
        $source = collect($sourceGroups)
            ->flatMap(fn (array $group) => $group['sources'])
            ->firstWhere('source', '教務處');

        expect($source)->not->toBeNull();

        $administrativeCategories->each(function (string $category) use ($source): void {
            expect($source['selectedCategories'])->toContain($category);
        });
    });
});

it('persists a submitted announcement category selection', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Persist Preferences',
    ]);

    $response = put(route('schedules.announcement-preferences.update', $schedule), [
        'announcement_categories' => [
            '教務處' => ['考試資訊'],
        ],
    ]);

    $response->assertRedirect(route('schedules.show', $schedule));

    $schedule->refresh();

    expect($schedule->announcement_categories)->toBe([
        '教務處' => ['考試資訊'],
    ]);
});

it('persists an explicitly empty selection as show-nothing rather than defaulting back to 各處室', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Clear Preferences',
        'announcement_categories' => ['教務處' => ['考試資訊']],
    ]);

    $response = put(route('schedules.announcement-preferences.update', $schedule), []);

    $response->assertRedirect(route('schedules.show', $schedule));

    $schedule->refresh();

    expect($schedule->announcement_categories)->toBe([]);
});

it('drops stale sources and categories that no longer exist in the config catalog', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Stale Preferences',
    ]);

    $response = put(route('schedules.announcement-preferences.update', $schedule), [
        'announcement_categories' => [
            '教務處' => ['考試資訊', '不存在的分類'],
            '不存在的來源' => ['某分類'],
        ],
    ]);

    $response->assertRedirect(route('schedules.show', $schedule));

    $schedule->refresh();

    expect($schedule->announcement_categories)->toBe([
        '教務處' => ['考試資訊'],
    ]);
});
