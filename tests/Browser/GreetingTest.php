<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

// The greeting text, date, and semester week are computed in the browser
// from the viewer's local clock (see window.nouToolsGreeting in resources/js/app.js),
// so these behaviours are only observable with a real browser, not the
// Feature tests that merely assert the config payload is embedded.

function expectedGreetingBucket(Carbon $now): string
{
    $hour = (int) $now->format('G');

    return match (true) {
        $hour >= 5 && $hour < 12 => '早安',
        $hour >= 12 && $hour < 18 => '午安',
        default => '晚安',
    };
}

function expectedGreetingDateString(Carbon $now): string
{
    return $now->format('Y').' 年 '.$now->format('n').' 月 '.$now->format('j').' 日 ('.chineseWeekdayChar($now).')';
}

function expectedCompactDateString(Carbon $now): string
{
    return $now->format('Y').'/'.$now->format('m').'/'.$now->format('d').' ('.chineseWeekdayChar($now).')';
}

it('renders the client-computed greeting and semester week for a viewer in Asia/Taipei', function () {
    $semesterStart = now('Asia/Taipei')->subWeeks(3)->startOfDay();

    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', [
        $semesterStart->toDateString(),
        now('Asia/Taipei')->addWeeks(3)->toDateString(),
    ]);

    $now = Carbon::now('Asia/Taipei');
    $daysSinceStart = intdiv($now->clone()->startOfDay()->getTimestamp() - $semesterStart->getTimestamp(), 86400);
    $expectedWeek = intdiv($daysSinceStart, 7) + 1;

    visit('/')
        ->withTimezone('Asia/Taipei')
        ->assertSee(expectedGreetingBucket($now))
        ->assertSee(expectedGreetingDateString($now))
        ->assertSee('114 學年度下學期第'.Str::toChineseNumber($expectedWeek).'週')
        ->screenshot();
});

it('renders the greeting from the viewer local clock when their timezone differs from Asia/Taipei', function () {
    $semesterStart = now('Asia/Taipei')->subWeeks(3)->startOfDay();

    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', [
        $semesterStart->toDateString(),
        now('Asia/Taipei')->addWeeks(3)->toDateString(),
    ]);

    // Fixed-offset zone (no DST) so the expected values below are stable.
    $timezone = 'Asia/Kolkata';
    $now = Carbon::now($timezone);

    visit('/')
        ->withTimezone($timezone)
        ->assertSee(expectedGreetingBucket($now))
        ->assertSee(expectedGreetingDateString($now))
        ->screenshot();
});

it('omits the semester week text when the semester range is not configured', function () {
    Config::set('app.current_semester', '2025B');
    Config::set('app.current_semester_range', []);

    visit('/')
        ->withTimezone('Asia/Taipei')
        ->assertSee('2025B')
        ->assertDontSee('尚未開始')
        ->assertDontSee('已結束')
        ->screenshot();
});

describe('Taiwan clock', function () {
    it('is hidden for a viewer already in Asia/Taipei', function () {
        visit('/')
            ->withTimezone('Asia/Taipei')
            ->assertMissing('[data-testid="taiwan-clock"]')
            ->screenshot();
    });

    it('shows the Taiwan time and date for a viewer outside Asia/Taipei', function () {
        // Fixed-offset zone (no DST) so the expected date below is stable.
        $timezone = 'Asia/Kolkata';
        $taipeiNow = Carbon::now('Asia/Taipei');

        $expectedDate = $taipeiNow->format('Y').'/'.$taipeiNow->format('n').'/'.$taipeiNow->format('j')
            .' ('.chineseWeekdayChar($taipeiNow).')';

        visit('/')
            ->withTimezone($timezone)
            ->assertVisible('[data-testid="taiwan-clock"]')
            ->assertSeeIn('[data-testid="taiwan-clock"]', $expectedDate)
            ->assertSeeIn('[data-testid="taiwan-clock"]', '台灣時間')
            ->screenshot();
    });

    it('is hidden for a viewer in a zone that shares Taipei\'s UTC offset', function () {
        // Asia/Shanghai has no DST and sits at the same UTC+8 offset as
        // Asia/Taipei, so the "your time differs" clock should stay hidden.
        visit('/')
            ->withTimezone('Asia/Shanghai')
            ->assertMissing('[data-testid="taiwan-clock"]')
            ->screenshot();
    });
});

describe('Compact mode', function () {
    it('switches from the normal to the compact widget when clicked, and back again', function () {
        visit('/')
            ->withTimezone('Asia/Taipei')
            ->assertVisible('[data-testid="greeting-normal"]')
            ->assertMissing('[data-testid="greeting-compact"]')
            ->click('[data-testid="greeting-widget"]')
            ->assertMissing('[data-testid="greeting-normal"]')
            ->assertVisible('[data-testid="greeting-compact"]')
            ->click('[data-testid="greeting-widget"]')
            ->assertVisible('[data-testid="greeting-normal"]')
            ->assertMissing('[data-testid="greeting-compact"]')
            ->screenshot();
    });

    it('renders the single-line date and semester week for a viewer in Asia/Taipei', function () {
        $semesterStart = now('Asia/Taipei')->subWeeks(3)->startOfDay();

        Config::set('app.current_semester', '2025B');
        Config::set('app.current_semester_range', [
            $semesterStart->toDateString(),
            now('Asia/Taipei')->addWeeks(3)->toDateString(),
        ]);

        $now = Carbon::now('Asia/Taipei');
        $daysSinceStart = intdiv($now->clone()->startOfDay()->getTimestamp() - $semesterStart->getTimestamp(), 86400);
        $expectedWeek = intdiv($daysSinceStart, 7) + 1;

        visit('/')
            ->withTimezone('Asia/Taipei')
            ->click('[data-testid="greeting-widget"]')
            ->assertSeeIn('[data-testid="greeting-compact"]', expectedCompactDateString($now))
            ->assertSeeIn('[data-testid="greeting-compact"]', '114 下 W'.$expectedWeek)
            ->assertMissing('[data-testid="taiwan-clock-compact"]')
            ->screenshot();
    });

    it('shows the Taiwan time inline for a viewer outside Asia/Taipei', function () {
        // Fixed-offset zone (no DST) so the expected values below are stable.
        $timezone = 'Asia/Kolkata';
        $before = Carbon::now('Asia/Taipei');

        $page = visit('/')
            ->withTimezone($timezone)
            ->click('[data-testid="greeting-widget"]')
            ->assertVisible('[data-testid="taiwan-clock-compact"]')
            ->assertSeeIn('[data-testid="taiwan-clock-compact"]', '台灣時間');

        $after = Carbon::now('Asia/Taipei');

        // The displayed clock is read from the browser's own wall clock, so
        // allow for a minute to have ticked over between capturing $before
        // and the assertion running, rather than asserting an exact value.
        // Walk whole-minute boundaries rather than diffInMinutes($before,
        // $after): that floors the raw elapsed time, so if $before sits near
        // the end of a minute the browser's clock can already show the next
        // minute even though less than 60s of wall time has passed, making
        // the acceptable set miss the actually-rendered value.
        $acceptable = collect();
        $cursor = $before->clone()->startOfMinute();
        $end = $after->clone()->startOfMinute();
        while ($cursor->lessThanOrEqualTo($end)) {
            $acceptable->push($cursor->format('H:i'));
            $cursor->addMinute();
        }

        $text = preg_replace('/\s+/', '', (string) $page->text('[data-testid="taiwan-clock-compact"]'));

        expect($acceptable->contains(fn (string $time) => str_contains($text, $time)))->toBeTrue();

        $page->screenshot();
    });

    it('omits the Taiwan time for a viewer already in Asia/Taipei', function () {
        visit('/')
            ->withTimezone('Asia/Taipei')
            ->click('[data-testid="greeting-widget"]')
            ->assertMissing('[data-testid="taiwan-clock-compact"]')
            ->screenshot();
    });
});
