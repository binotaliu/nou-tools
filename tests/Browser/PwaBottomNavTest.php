<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;

// The installed PWA swaps the web-style header menu for a bottom tab bar on
// phone-sized screens. `html[data-pwa]` is what the root view's head script
// sets for a standalone display mode; a headless browser tab isn't
// standalone, so it's set by hand. Viewports are explicit because both the
// header menu and the bottom bar depend on the `md` breakpoint (768px).

const PHONE = [390, 844];
const TABLET = [820, 1180];
const DESKTOP = [1280, 800];

// assertVisible() doesn't retry, so wait for the nav's own text (which does)
// so Vue has mounted and the resize has been laid out before reading the DOM.
$waitForHeaderNav = function ($page) {
    return $page->assertSee('自習室')->assertVisible('[data-testid="header-nav"]');
};

$enterPwaMode = function ($page): void {
    $page->script("document.documentElement.dataset.pwa = ''");
};

$linkTexts = function ($page, string $selector): array {
    return json_decode(
        $page->script("JSON.stringify([...document.querySelectorAll('{$selector}')].map(a => a.textContent.trim()))"),
        true,
    );
};

it('shows the hamburger menu and no bottom tab bar in a normal phone browser tab', function () {
    $page = visit('/announcements')->resize(...PHONE);

    $page->assertSee('學校公告')
        ->assertVisible('[data-testid="header-menu-toggle"]')
        ->assertMissing('[data-testid="bottom-nav"]');
});

it('swaps the hamburger menu for a bottom tab bar when running as an installed PWA on a phone', function () use ($enterPwaMode) {
    $page = visit('/announcements')->resize(...PHONE);

    $enterPwaMode($page);

    $page->assertVisible('[data-testid="bottom-nav"]')
        ->assertMissing('[data-testid="header-menu-toggle"]')
        ->assertMissing('[data-testid="bottom-nav-sheet"]');

    $activeTab = $page->script('document.querySelector(\'[data-testid="bottom-nav"] [aria-current="page"]\').textContent.trim()');

    expect($activeTab)->toBe('學校公告');

    // The active tab carries a bar on its top edge (and only that tab).
    $indicators = $page->script('document.querySelectorAll(\'[data-testid="bottom-nav-indicator"]\').length');
    $indicatorInActiveTab = $page->script('document.querySelectorAll(\'[data-testid="bottom-nav"] [aria-current="page"] [data-testid="bottom-nav-indicator"]\').length');

    expect($indicators)->toBe(1)->and($indicatorInActiveTab)->toBe(1);
});

it('keeps the header menu and hides the bottom tab bar in an installed PWA on tablet and desktop', function (array $viewport) use ($waitForHeaderNav, $enterPwaMode) {
    $page = visit('/announcements')->resize(...$viewport);

    $enterPwaMode($page);

    $waitForHeaderNav($page)->assertMissing('[data-testid="bottom-nav"]');
})->with([
    'tablet' => [TABLET],
    'desktop' => [DESKTOP],
]);

it('opens the more sheet from the tab bar and closes it again', function () use ($enterPwaMode) {
    $page = visit('/announcements')->resize(...PHONE);

    $enterPwaMode($page);

    $page->screenshot(filename: 'pwa-bottom-nav-phone')
        ->click('[data-testid="bottom-nav-more"]')
        ->assertVisible('[data-testid="bottom-nav-sheet"]')
        ->assertSee('浣熊的空大雙週報')
        ->assertSee('關於本站');

    // The sheet covers the middle of the backdrop, where a driver click would
    // land, so dispatch the click on the backdrop itself. Then let the 150ms
    // leave transition finish before asserting it's gone.
    $page->script("document.querySelector('[data-testid=\"bottom-nav-backdrop\"]').click()");

    waitUntil($page, 'document.querySelector(\'[data-testid="bottom-nav-sheet"]\') === null');

    $page->assertMissing('[data-testid="bottom-nav-sheet"]');
});

it('puts learning progress after my schedule in both navs and moves Alt UU and discount stores into more', function () use ($waitForHeaderNav, $enterPwaMode, $linkTexts) {
    $page = $waitForHeaderNav(visit('/announcements')->resize(...DESKTOP));

    expect($linkTexts($page, '[data-testid="header-nav"] > a'))
        ->toBe(['我的課表', '學習進度', '自習室', '學校公告', '優惠店家']);

    $page->resize(...PHONE);
    $enterPwaMode($page);

    expect($linkTexts($page, '[data-testid="bottom-nav"] a'))
        ->toBe(['我的課表', '學習進度', '自習室', '學校公告']);

    $page->click('[data-testid="bottom-nav-more"]');

    expect($linkTexts($page, '[data-testid="bottom-nav-sheet"] a'))
        ->toContain('優惠店家', 'Alt UU', '今日視訊面授');
});

it('highlights learning progress, not my schedule, on a learning progress page', function () use ($waitForHeaderNav, $linkTexts) {
    $schedule = StudentSchedule::factory()->create();
    $courseClass = CourseClass::factory()
        ->for(Course::factory()->state(['term' => config('app.current_semester')]))
        ->create();
    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);

    $page = $waitForHeaderNav(
        visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => config('app.current_semester')], absolute: false))
            ->resize(...DESKTOP)
    );

    expect($linkTexts($page, '[data-testid="header-nav"] > a[aria-current="page"]'))->toBe(['學習進度']);
});

it('collapses the schedule page actions into one menu in an installed PWA on a phone', function () use ($enterPwaMode, $linkTexts) {
    $schedule = StudentSchedule::factory()->create();

    $page = visit(route('schedules.show', $schedule, absolute: false))->resize(...PHONE);

    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'#term\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }

    // In a plain browser tab the buttons stay inline and there is no menu.
    $page->assertMissing('[data-testid="schedule-actions-toggle"]');

    $enterPwaMode($page);

    $page->assertVisible('[data-testid="schedule-actions-toggle"]')
        ->assertMissing('[data-testid="schedule-actions-menu"]')
        ->assertMissing('a[data-analytics-event="calendar_subscribe_open"]')
        ->assertMissing('a[data-analytics-event="schedule_edit"]')
        ->assertVisible('#term');

    $page->screenshot(filename: 'pwa-schedule-actions-closed')
        ->click('[data-testid="schedule-actions-toggle"]')
        ->assertVisible('[data-testid="schedule-actions-menu"]')
        ->screenshot(filename: 'pwa-schedule-actions-open');

    expect($linkTexts($page, '[data-testid="schedule-actions-menu"] a'))
        ->toBe(['學習進度表', '訂閱行事曆', '編輯', '自訂']);

    // Tapping outside dismisses it.
    $page->click('[data-testid="schedule-title"]')->assertMissing('[data-testid="schedule-actions-menu"]');
});

it('keeps the schedule page actions inline in an installed PWA on a tablet', function () use ($enterPwaMode) {
    $schedule = StudentSchedule::factory()->create();

    $page = visit(route('schedules.show', $schedule, absolute: false))->resize(...TABLET);

    waitUntil($page, 'document.querySelector(\'#term\') !== null');
    $enterPwaMode($page);

    $page->assertMissing('[data-testid="schedule-actions-toggle"]')
        ->assertVisible('a[data-analytics-event="calendar_subscribe_open"]');
});

it('hides the learning progress header in a phone PWA and offers a floating save button once edited', function () use ($enterPwaMode) {
    $schedule = StudentSchedule::factory()->create();
    $courseClass = CourseClass::factory()
        ->for(Course::factory()->state(['term' => config('app.current_semester')]))
        ->create();
    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);

    $page = visit(route('learning-progress.show', ['schedule' => $schedule, 'term' => config('app.current_semester')], absolute: false))
        ->resize(...PHONE);

    $page->assertVisible('[data-testid="learning-progress-header"]')
        ->assertMissing('[data-testid="learning-progress-floating-save"]');

    $enterPwaMode($page);

    $page->assertMissing('[data-testid="learning-progress-header"]')
        ->assertMissing('[data-testid="learning-progress-floating-save"]');

    $page->script("(() => { const box = document.querySelector('#progress-form input[type=checkbox]'); box.click(); })()");

    $page->assertVisible('[data-testid="learning-progress-floating-save"]');
});

it('hides the page footer in a phone PWA while About carries the disclaimer and contact info', function () use ($enterPwaMode) {
    $page = visit('/announcements')->resize(...PHONE);

    $page->assertSee('學校公告')->assertVisible('[data-testid="site-footer"]');

    $enterPwaMode($page);

    $page->assertMissing('[data-testid="site-footer"]');

    visit('/about')->resize(...PHONE)
        ->assertSee('免責聲明')
        ->assertVisible('[data-testid="about-brand"]')
        ->assertVisible('[data-testid="about-title"]')
        ->assertSee('給 NOU 同學的非官方小工具')
        ->assertVisible('[data-testid="about-disclaimer"]')
        ->assertVisible('[data-testid="about-contact"]')
        ->assertSee('nou-tools-contact@binota.org');
});

it('drops the About page title and subtitle in a phone PWA but keeps the brand block', function () use ($enterPwaMode) {
    $page = visit('/about')->resize(...PHONE);

    $page->assertVisible('[data-testid="about-title"]')
        ->assertVisible('[data-testid="about-subtitle"]');

    $enterPwaMode($page);

    $page->assertMissing('[data-testid="about-title"]')
        ->assertMissing('[data-testid="about-subtitle"]')
        ->assertVisible('[data-testid="about-brand"]');
});

it('drops the double-tap zoom delay only in an installed PWA', function () use ($enterPwaMode) {
    $page = visit('/announcements')->resize(...PHONE);

    $page->assertSee('學校公告');
    expect($page->script('getComputedStyle(document.documentElement).touchAction'))->toBe('auto');

    $enterPwaMode($page);

    expect($page->script('getComputedStyle(document.documentElement).touchAction'))->toBe('manipulation');
});

it('hides the header in a phone PWA and links 設定 from the more sheet', function () use ($enterPwaMode, $linkTexts) {
    $page = visit('/announcements')->resize(...PHONE);

    dismissCookieConsentBanner($page);

    $page->assertSee('學校公告')->assertVisible('[data-testid="header-menu-toggle"]');

    $enterPwaMode($page);

    $page->assertMissing('[data-testid="header-menu-toggle"]')
        ->assertMissing('[data-testid="site-header"]')
        ->click('[data-testid="bottom-nav-more"]');

    expect($linkTexts($page, '[data-testid="bottom-nav-sheet"] a'))->toContain('設定');

    $page->click('[data-testid="bottom-nav-sheet"] a[href$="/settings"]')
        ->waitForEvent('load')
        ->assertVisible('[data-testid="settings-title"]');
});

it('keeps the header on a tablet PWA', function () use ($waitForHeaderNav, $enterPwaMode) {
    $page = visit('/announcements')->resize(...TABLET);

    $enterPwaMode($page);

    $waitForHeaderNav($page)->assertVisible('[data-testid="site-header"]');
});

it('changes the theme and accent from the settings page', function () {
    $page = visit('/settings')->resize(...PHONE);

    $page->assertVisible('[data-testid="settings-appearance"]')
        ->click('[data-testid="settings-appearance"] [role="tab"]:nth-child(3)');

    expect($page->script("document.documentElement.classList.contains('dark')"))->toBeTrue();
    expect($page->script("localStorage.getItem('theme')"))->toBe('dark');

    $page->click('[data-testid="settings-appearance"] button[aria-label="海藍"]');

    expect($page->script('document.documentElement.dataset.accent'))->toBe('ocean');
});

it('points a visitor without a remembered schedule to their schedule instead of showing notification switches', function () {
    $page = visit('/settings')->resize(...PHONE);

    $page->assertVisible('[data-testid="settings-notifications"]')
        ->assertVisible('[data-testid="settings-notifications-no-schedule"]')
        ->assertMissing('[data-testid="settings-notify-class-reminders"]')
        ->assertMissing('[data-testid="settings-notify-timer-end"]');
});
