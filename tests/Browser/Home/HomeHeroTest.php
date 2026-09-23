<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;

// The hero cross-fades between three slides that all sit in the DOM; the
// inactive ones are `inert` + aria-hidden, so "which slide is showing" is
// read from that attribute rather than from visible text. Autoplay is
// stopped first with the pause control so slide timing can't race the checks.

function activeHeroSlide($page): mixed
{
    return $page->script(<<<'JS'
        [...document.querySelectorAll('[data-testid^="hero-slide-"]')]
            .find(slide => !slide.inert)
            ?.dataset.testid
    JS);
}

it('promotes the three features, each with an illustration and the unofficial tagline', function () {
    $page = visit(route('home'))->assertPresent('[data-testid="home-hero"]');

    $summary = $page->script(<<<'JS'
        [...document.querySelectorAll('[data-testid^="hero-slide-"]')].map(slide => ({
            id: slide.dataset.testid,
            text: slide.innerText,
            illustrations: slide.querySelectorAll('svg[aria-hidden="true"]').length,
        }))
    JS);

    expect($summary)->toHaveCount(3);

    $expected = [
        'hero-slide-schedule' => ['課表管理', '方便檢視下次面授時間'],
        'hero-slide-learning-progress' => ['學習進度表', '掌握自己的進度'],
        'hero-slide-study-room' => ['自習室', '與同學在雲端一起用功'],
    ];

    foreach ($summary as $slide) {
        expect($slide['illustrations'])->toBeGreaterThanOrEqual(1);
        expect($slide['text'])->toContain('為空大同學製作的非官方小工具');

        foreach ($expected[$slide['id']] as $copy) {
            expect($slide['text'])->toContain($copy);
        }
    }
});

it('moves between slides with the arrows and dots, wrapping at both ends', function () {
    $page = visit(route('home'))->assertPresent('[data-testid="home-hero"]');
    $page->script("document.querySelector('[data-testid=\"hero-toggle-autoplay\"]').click()");

    expect(activeHeroSlide($page))->toBe('hero-slide-schedule');

    $page->script("document.querySelector('[data-testid=\"hero-next\"]').click()");
    expect(activeHeroSlide($page))->toBe('hero-slide-learning-progress');

    $page->script("document.querySelector('[data-testid=\"hero-dot-study-room\"]').click()");
    expect(activeHeroSlide($page))->toBe('hero-slide-study-room');

    $page->script("document.querySelector('[data-testid=\"hero-next\"]').click()");
    expect(activeHeroSlide($page))->toBe('hero-slide-schedule');

    $page->script("document.querySelector('[data-testid=\"hero-previous\"]').click()");
    expect(activeHeroSlide($page))->toBe('hero-slide-study-room');
});

it('links each slide to schedule creation', function () {
    $page = visit(route('home'))->assertPresent('[data-testid="home-hero"]');

    $hrefs = $page->script(<<<'JS'
        [...document.querySelectorAll('[data-testid^="hero-slide-"] a')].map(a => a.getAttribute('href'))
    JS);

    expect($hrefs)->toBe(['/schedules/my', '/schedules/my', '/schedules/my']);
});

it('hides the hero from visitors who already saved a schedule', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Hero Hidden Schedule',
    ]);

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]')
        ->waitForEvent('load');

    $page->navigate(route('home'))
        ->assertSee('Hero Hidden Schedule')
        ->assertMissing('[data-testid="home-hero"]');
});
