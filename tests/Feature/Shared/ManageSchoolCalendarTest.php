<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\ManageSchoolCalendar;
use App\Models\SchoolCalendarEvent;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    config(['app.current_semester' => '2026A']);

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('is admin only', function () {
    actingAs(User::factory()->create());

    get('/admin/school-calendar')->assertForbidden();
});

it('loads the current semester events into the editor', function () {
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-11-07', '2026-11-08')->countdown()->create(['name' => '期中考']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->create(['name' => '別學期']);

    $component = Livewire::test(ManageSchoolCalendar::class)
        ->assertSet('data.term', '2026A')
        ->assertCount('data.events', 1);

    $event = collect($component->get('data.events'))->first();

    expect($event)->toMatchArray(['name' => '期中考', 'is_countdown' => true, 'is_important' => true])
        ->and($event['start_date'])->toStartWith('2026-11-07')
        ->and($event['end_date'])->toStartWith('2026-11-08');
});

it('switches semesters and shows that semester’s events', function () {
    SchoolCalendarEvent::factory()->forTerm('2025B')->create(['name' => '下學期活動']);

    Livewire::test(ManageSchoolCalendar::class)
        ->fillForm(['term' => '2025B'])
        ->assertCount('data.events', 1)
        ->assertSet('data.events', fn (array $events) => collect($events)->pluck('name')->contains('下學期活動'));
});

it('edits, adds and removes events in one save, leaving other semesters alone', function () {
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-09-07', '2026-09-07')->create(['name' => '舊名稱']);
    SchoolCalendarEvent::factory()->forTerm('2026A')->create(['name' => '要刪除']);
    $other = SchoolCalendarEvent::factory()->forTerm('2025B')->create(['name' => '其他學期']);

    $component = Livewire::test(ManageSchoolCalendar::class);
    $events = $component->get('data.events');
    $removeKey = collect($events)->search(fn (array $event) => $event['name'] === '要刪除');
    unset($events[$removeKey]);
    $keptKey = collect($events)->search(fn (array $event) => $event['name'] === '舊名稱');
    $events[$keptKey]['name'] = '新名稱';
    $events[$keptKey]['is_important'] = false;
    $events['new'] = [
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-20',
        'name' => '選課',
        'is_countdown' => false,
        'is_important' => true,
    ];

    $component->set('data.events', $events)->call('save')->assertHasNoFormErrors()->assertNotified();

    $saved = SchoolCalendarEvent::query()->forTerm('2026A')->orderBy('start_date')->get();

    expect($saved->pluck('name')->all())->toBe(['新名稱', '選課'])
        ->and($saved[0]->is_important)->toBeFalse()
        ->and($saved[1]->end_date->toDateString())->toBe('2026-12-20')
        ->and(SchoolCalendarEvent::query()->whereKey($other->id)->exists())->toBeTrue();
});

it('rejects an event that ends before it starts', function () {
    Livewire::test(ManageSchoolCalendar::class)
        ->fillForm(['events' => [
            'new' => ['start_date' => '2026-12-20', 'end_date' => '2026-12-01', 'name' => '顛倒', 'is_countdown' => false, 'is_important' => true],
        ]])
        ->call('save')
        ->assertHasFormErrors(['events.new.end_date']);

    expect(SchoolCalendarEvent::query()->count())->toBe(0);
});

it('requires a name', function () {
    Livewire::test(ManageSchoolCalendar::class)
        ->fillForm(['events' => [
            'new' => ['start_date' => '2026-12-01', 'end_date' => '2026-12-01', 'name' => '', 'is_countdown' => false, 'is_important' => true],
        ]])
        ->call('save')
        ->assertHasFormErrors(['events.new.name' => 'required']);
});
