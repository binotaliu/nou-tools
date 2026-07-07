<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

it('renders the grouped announcement source catalog', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Preferences Test',
    ]);

    $response = get(route('schedules.announcement-preferences', $schedule));

    $response->assertSuccessful();
    $response->assertSee('各處室');
    $response->assertSee('學系');
    $response->assertSee('學習指導中心');
    $response->assertSee('學校首頁');
    $response->assertSee('通識博雅教育中心');
    $response->assertSee('海外學生服務組');
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

    $response = get(route('schedules.announcement-preferences', $schedule));

    $response->assertSuccessful();

    $administrativeCategories->each(function (string $category) use ($response): void {
        $response->assertSee($category, false);
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
