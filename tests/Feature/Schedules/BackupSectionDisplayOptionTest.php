<?php

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\PageData\ScheduleCustomizationPageData;

$migration = fn () => require base_path('database/migrations/2026_10_03_120000_rename_show_share_section_display_option.php');

it('renames the stored show_share_section key and keeps its value', function () use ($migration) {
    $hidden = StudentSchedule::factory()->create(['display_options' => ['show_share_section' => false, 'show_greeting' => true]]);
    $untouched = StudentSchedule::factory()->create(['display_options' => ['show_greeting' => false]]);
    $none = StudentSchedule::factory()->create(['display_options' => null]);

    $migration()->up();

    expect($hidden->refresh()->display_options)->toBe(['show_greeting' => true, 'show_backup_section' => false])
        ->and($untouched->refresh()->display_options)->toBe(['show_greeting' => false])
        ->and($none->refresh()->display_options)->toBeNull();
});

it('prefers an explicit show_backup_section over the old key when both are stored', function () use ($migration) {
    $schedule = StudentSchedule::factory()->create(['display_options' => ['show_share_section' => true, 'show_backup_section' => false]]);

    $migration()->up();

    expect($schedule->refresh()->display_options)->toBe(['show_backup_section' => false]);
});

it('can be rolled back', function () use ($migration) {
    $schedule = StudentSchedule::factory()->create(['display_options' => ['show_backup_section' => false]]);

    $migration()->down();

    expect($schedule->refresh()->display_options)->toBe(['show_share_section' => false]);
});

it('still honours the old key for a row that has not been migrated', function () {
    $options = ScheduleCustomizationPageData::normalizeDisplayOptions(['show_share_section' => false]);

    expect($options->showBackupSection)->toBeFalse();
});

it('reads the new key and defaults to showing the card', function () {
    expect(ScheduleCustomizationPageData::normalizeDisplayOptions(['show_backup_section' => false])->showBackupSection)->toBeFalse()
        ->and(ScheduleCustomizationPageData::normalizeDisplayOptions([])->showBackupSection)->toBeTrue();
});
