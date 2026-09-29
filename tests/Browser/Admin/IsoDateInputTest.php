<?php

use App\Enums\UserRole;
use App\Models\CalendarDay;
use App\Models\User;
use Carbon\CarbonImmutable;

// The date fields on the calendar admin pages are typed as YYYY-MM-DD and also
// have a calendar button, which only a real browser can exercise.

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-09-29');
    $this->actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('accepts a typed YYYY-MM-DD date and saves it', function () {
    visit('/admin/calendar-days')
        ->assertNoJavaScriptErrors()
        ->click('新增標註')
        ->type('input[placeholder="YYYY-MM-DD"]', '20261010')
        ->assertValue('input[placeholder="YYYY-MM-DD"]', '2026-10-10')
        ->click('儲存')
        ->assertSee('已儲存日曆標註');

    expect(CalendarDay::query()->whereDate('date', '2026-10-10')->exists())->toBeTrue();
});

it('fills the field from the calendar button', function () {
    $page = visit('/admin/calendar-days')
        ->click('新增標註')
        ->assertPresent('button[aria-label="開啟日曆"]');

    $page->script("const input = document.querySelector('input[type=date]'); input.value = '2026-11-11'; input.dispatchEvent(new Event('change', { bubbles: true }));");

    $page->assertValue('input[placeholder="YYYY-MM-DD"]', '2026-11-11')
        ->click('儲存')
        ->assertSee('已儲存日曆標註');

    expect(CalendarDay::query()->whereDate('date', '2026-11-11')->exists())->toBeTrue();
});
