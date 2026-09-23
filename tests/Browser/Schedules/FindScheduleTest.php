<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;

it('lets a visitor with no remembered schedule choose to create one', function () {
    visit(route('schedules.my'))
        ->assertSee('你之前建立過課表嗎？')
        ->assertMissing('[data-testid="find-schedule-form"]')
        ->assertVisible('[data-testid="find-schedule-new"]');
});

it('remembers a previously created schedule from its pasted link', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Browser Find Schedule',
    ]);

    $page = visit(route('schedules.my'));

    $page->click('[data-testid="find-schedule-existing"]')
        ->assertVisible('[data-testid="find-schedule-form"]')
        ->assertSee('如果你之前訂閱過行事曆，也可以在行事曆行程中的備註內找到課表連結。')
        ->fill('[data-testid="find-schedule-url"]', route('schedules.show', $schedule))
        ->click('[data-testid="find-schedule-submit"]')
        ->assertPathIs('/schedules/'.$schedule->getRouteKey())
        ->assertSee('Browser Find Schedule')
        // Remembered now, so the "remember this schedule?" prompt is skipped.
        ->assertMissing('[data-testid="remember-schedule-modal"]');
});

it('shows an error when the pasted link is not a schedule', function () {
    visit(route('schedules.my'))
        ->click('[data-testid="find-schedule-existing"]')
        ->fill('[data-testid="find-schedule-url"]', 'https://nou.tools/schedules/AAAAAAAAAAAAAAAAAAAAAA')
        ->click('[data-testid="find-schedule-submit"]')
        ->assertSee('找不到這個課表');
});

it('says 裝置 instead of 瀏覽器 when running as an installed PWA', function () {
    $page = visit(route('schedules.my'))->assertSee('我的課表');

    // Both wordings are in the DOM and CSS picks one, so read what is
    // actually rendered (innerText skips display:none) rather than assertSee.
    $intro = fn () => $page->script("document.querySelector('h2 + p').innerText");

    expect($intro())->toContain('這個瀏覽器上還沒有記住任何課表');

    // A headless tab isn't standalone; set the flag the root view's head
    // script would (see PwaBottomNavTest).
    $page->script("document.documentElement.dataset.pwa = ''");

    expect($intro())->toContain('這個裝置上還沒有記住任何課表');
});
