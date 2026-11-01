<?php

use App\Enums\BaristaTrigger;
use App\Enums\UserRole;
use App\Filament\Resources\CafeBaristaLines\Pages\CreateCafeBaristaLine;
use App\Filament\Resources\CafeBaristaLines\Pages\EditCafeBaristaLine;
use App\Filament\Resources\CafeBaristaLines\Pages\ListCafeBaristaLines;
use App\Filament\Resources\CafeMenuItems\Pages\CreateCafeMenuItem;
use App\Filament\Resources\CafeMenuItems\Pages\ListCafeMenuItems;
use App\Models\CafeBaristaLine;
use App\Models\CafeMenuItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('lists and edits the default barista lines', function () {
    $line = CafeBaristaLine::query()->where('trigger', BaristaTrigger::FirstVisit)->firstOrFail();

    Livewire::test(ListCafeBaristaLines::class)
        ->assertCanSeeTableRecords(CafeBaristaLine::query()->where('priority', '>=', 50)->get());

    Livewire::test(EditCafeBaristaLine::class, ['record' => $line->id])
        ->fillForm(['text' => '歡迎第一次光臨！'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($line->fresh()->text)->toBe('歡迎第一次光臨！');
});

it('creates a time-of-day line with conditions and replies', function () {
    Livewire::test(CreateCafeBaristaLine::class)
        ->fillForm([
            'trigger' => BaristaTrigger::TimeOfDay->value,
            'text' => '午安，來杯咖啡吧。',
            'mood' => 'happy',
            'priority' => 10,
            'weight' => 1,
            'is_active' => true,
            'conditions' => ['period' => 'afternoon'],
            'replies' => [['label' => '好啊', 'response' => '馬上來。', 'action' => 'order']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $line = CafeBaristaLine::query()->where('text', '午安，來杯咖啡吧。')->firstOrFail();

    expect($line->conditions['period'])->toBe('afternoon')
        ->and($line->replies[0]['action'])->toBe('order');
});

it('requires a period for a time-of-day line', function () {
    Livewire::test(CreateCafeBaristaLine::class)
        ->fillForm([
            'trigger' => BaristaTrigger::TimeOfDay->value,
            'text' => '沒有時段',
            'mood' => 'happy',
            'priority' => 0,
            'weight' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['conditions.period' => 'required']);
});

it('manages the menu', function () {
    Livewire::test(ListCafeMenuItems::class)
        ->assertCanSeeTableRecords(CafeMenuItem::query()->get());

    Livewire::test(CreateCafeMenuItem::class)
        ->fillForm(['name' => '虛構特調', 'emoji' => '🧋', 'cup_style' => 'glass', 'price' => 4, 'is_active' => true, 'sort' => 9])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(CafeMenuItem::query()->where('name', '虛構特調')->value('price'))->toBe(4);
});
