<?php

declare(strict_types=1);

use App\Models\CalendarDay;
use App\Models\SchoolCalendarEvent;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.current_semester' => '2026A']);
});

it('lists every event of the current semester, important or not', function () {
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-09-07', '2026-09-07')->create(['name' => '課程開播']);
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-08-01', '2026-08-01')->minor()->create(['name' => '學期開始']);
    SchoolCalendarEvent::factory()->forTerm('2025B')->create(['name' => '別學期']);

    $this->get(route('school-calendar.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolCalendar/Index')
            ->where('viewModel.term', '2026A')
            ->where('viewModel.termLabel', '115 學年度上學期')
            ->where('viewModel.isCurrentTerm', true)
            ->where('viewModel.events', fn ($events) => collect($events)->pluck('name')->all() === ['學期開始', '課程開播'])
            ->where('viewModel.events.0.important', false)
            ->where('viewModel.events.1.important', true));
});

it('sends the day marks that fall inside the semester’s span', function () {
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-09-07', '2026-10-31')->create();
    CalendarDay::factory()->on('2026-10-10')->labelled('國慶日')->create();
    CalendarDay::factory()->on('2026-10-17')->plain()->labelled('補班')->create();
    CalendarDay::factory()->on('2026-11-01')->create();

    $this->get(route('school-calendar.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('viewModel.days', 2)
            ->where('viewModel.days.0', ['date' => '2026-10-10', 'isRed' => true, 'label' => '國慶日'])
            ->where('viewModel.days.1.isRed', false));
});

it('sends no day marks for a semester without events', function () {
    CalendarDay::factory()->on('2026-10-10')->create();

    $this->get(route('school-calendar.index'))
        ->assertInertia(fn (Assert $page) => $page->where('viewModel.days', []));
});

it('shows another semester when one is requested and offers every known term', function () {
    SchoolCalendarEvent::factory()->forTerm('2025B')->create(['name' => '下學期活動']);
    SchoolCalendarEvent::factory()->forTerm('2026A')->create();

    $this->get(route('school-calendar.index', ['term' => '2025B']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.term', '2025B')
            ->where('viewModel.isCurrentTerm', false)
            ->where('viewModel.terms', fn ($terms) => collect($terms)->pluck('code')->all() === ['2026A', '2025B'])
            ->where('viewModel.events.0.name', '下學期活動'));
});

it('rejects a malformed term', function () {
    $this->get(route('school-calendar.index', ['term' => 'nope']))->assertRedirect();
});

it('serves a markdown twin', function () {
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-11-07', '2026-11-08')->create(['name' => '期中考']);
    SchoolCalendarEvent::factory()->forTerm('2026A')->between('2026-09-07', '2026-09-07')->minor()->create(['name' => '課程開播']);

    $this->get(route('school-calendar.index.md'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# 學校行事曆')
        ->assertSee("- 2026-09-07：課程開播\n- 2026-11-07 ～ 2026-11-08：期中考", false);
});

it('says so in the markdown twin when a semester has no events', function () {
    $this->get(route('school-calendar.index.md', ['term' => '2099A']))
        ->assertOk()
        ->assertSee('本學期尚無行事曆資料。');
});

it('is listed in the sitemap', function () {
    $this->get(route('sitemap'))->assertSee(route('school-calendar.index'), false);
});
