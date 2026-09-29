<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\ManageCalendarDays;
use App\Models\CalendarDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-09-29');

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('is admin only', function () {
    actingAs(User::factory()->create());

    get('/admin/calendar-days')->assertForbidden();
});

it('loads the current year’s marks into the editor', function () {
    CalendarDay::factory()->on('2026-10-10')->labelled('國慶日')->create();
    CalendarDay::factory()->on('2027-01-01')->create();

    $component = Livewire::test(ManageCalendarDays::class)
        ->assertSet('data.year', 2026)
        ->assertCount('data.days', 1);

    expect(collect($component->get('data.days'))->first())
        ->toMatchArray(['label' => '國慶日', 'is_red' => true]);
});

it('switches year and shows that year’s marks', function () {
    CalendarDay::factory()->on('2027-01-01')->labelled('元旦')->create();

    Livewire::test(ManageCalendarDays::class)
        ->fillForm(['year' => 2027])
        ->assertCount('data.days', 1);
});

it('adds, edits and removes marks in one save, leaving other years alone', function () {
    CalendarDay::factory()->on('2026-02-28')->labelled('舊')->create();
    CalendarDay::factory()->on('2026-04-04')->create();
    CalendarDay::factory()->on('2027-01-01')->create();

    $component = Livewire::test(ManageCalendarDays::class);
    $days = $component->get('data.days');
    $removeKey = collect($days)->search(fn (array $day) => str_starts_with($day['date'], '2026-04-04'));
    unset($days[$removeKey]);
    $editKey = collect($days)->search(fn (array $day) => str_starts_with($day['date'], '2026-02-28'));
    $days[$editKey]['label'] = '和平紀念日';
    $days['new'] = ['date' => '2026-10-17', 'is_red' => false, 'label' => ''];

    $component->set('data.days', $days)->call('save')->assertHasNoFormErrors()->assertNotified();

    $saved = CalendarDay::query()->orderBy('date')->get();

    expect($saved->map(fn (CalendarDay $day) => $day->date->toDateString())->all())
        ->toBe(['2026-02-28', '2026-10-17', '2027-01-01'])
        ->and($saved[0]->label)->toBe('和平紀念日')
        ->and($saved[1]->is_red)->toBeFalse()
        ->and($saved[1]->label)->toBeNull();
});

it('rejects a date outside the selected year', function () {
    Livewire::test(ManageCalendarDays::class)
        ->fillForm(['days' => [
            'new' => ['date' => '2027-01-01', 'is_red' => true, 'label' => ''],
        ]])
        ->call('save')
        ->assertHasFormErrors(['days.new.date']);

    expect(CalendarDay::query()->count())->toBe(0);
});

it('rejects the same date twice', function () {
    Livewire::test(ManageCalendarDays::class)
        ->fillForm(['days' => [
            'a' => ['date' => '2026-10-10', 'is_red' => true, 'label' => ''],
            'b' => ['date' => '2026-10-10', 'is_red' => false, 'label' => ''],
        ]])
        ->call('save')
        ->assertHasFormErrors();
});
