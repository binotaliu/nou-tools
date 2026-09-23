<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Str;

// The banner only appears once the browser calls it installable, and the
// "don't ask again" choice lives in localStorage, so both are staged by hand
// in a headless tab: `beforeinstallprompt` is dispatched the way Chrome would
// (app.js captures it), and the storage key is cleared before each visit.
//
// The remember-schedule modal is dismissed from script() rather than click():
// click() has no bounded wait, so it can hang when the modal isn't there.

function makeInstallBannerSchedule(): StudentSchedule
{
    return StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Install Banner Schedule',
    ]);
}

function visitScheduleWithoutInstallChoice(StudentSchedule $schedule)
{
    // Storage is per-origin and outlives a test, so clear it and reload: the
    // composable reads it on mount.
    visit(route('schedules.show', $schedule))->script("localStorage.removeItem('pwa_install_banner_dismissed_v1')");

    $page = visit(route('schedules.show', $schedule))->assertSee('Install Banner Schedule');

    $page->script("document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')?.click()");

    return $page;
}

function makeInstallable($page): void
{
    $page->script(<<<'JS'
        (() => {
            const event = new Event('beforeinstallprompt', { cancelable: true })
            event.prompt = () => {}
            event.userChoice = Promise.resolve({ outcome: 'dismissed' })
            window.dispatchEvent(event)
        })()
    JS);
}

function bannerIsShown($page): bool
{
    return $page->script("getComputedStyle(document.querySelector('[data-testid=\"pwa-banner\"]')).display !== 'none'");
}

it('puts the install banner above everything else on the schedule page', function () {
    $page = visitScheduleWithoutInstallChoice(makeInstallBannerSchedule());
    makeInstallable($page);

    $page->assertVisible('[data-testid="pwa-banner"]');

    $isBeforeTitle = $page->script(<<<'JS'
        !!(document.querySelector('[data-testid="pwa-banner"]').compareDocumentPosition(
            document.querySelector('[data-testid="schedule-title"]')
        ) & Node.DOCUMENT_POSITION_FOLLOWING)
    JS);

    expect($isBeforeTitle)->toBeTrue();
});

it('remembers "不再提示我安裝", points to the footer, and stays hidden after a refresh', function () {
    $schedule = makeInstallBannerSchedule();
    $page = visitScheduleWithoutInstallChoice($schedule);
    makeInstallable($page);

    $page->assertVisible('[data-testid="pwa-banner-opt-out"]')
        ->click('[data-testid="pwa-banner-opt-out"]')
        ->assertVisible('[data-testid="pwa-banner-notice"]')
        ->assertSee('頁面最下方的「安裝 NOU 小幫手」')
        ->assertMissing('[data-testid="pwa-banner-opt-out"]');

    expect($page->script("localStorage.getItem('pwa_install_banner_dismissed_v1')"))->toBe('1');

    $page->click('[data-testid="pwa-banner-notice-ok"]');

    expect(bannerIsShown($page))->toBeFalse();

    $page->navigate(route('schedules.show', $schedule))->assertSee('Install Banner Schedule');
    makeInstallable($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="pwa-banner"]\') !== null');

    expect(bannerIsShown($page))->toBeFalse();
});

it('only hides the banner for this visit when it is closed with the X', function () {
    $schedule = makeInstallBannerSchedule();
    $page = visitScheduleWithoutInstallChoice($schedule);
    makeInstallable($page);

    $page->assertVisible('[data-testid="pwa-banner-close"]')
        ->click('[data-testid="pwa-banner-close"]');

    expect(bannerIsShown($page))->toBeFalse()
        ->and($page->script("localStorage.getItem('pwa_install_banner_dismissed_v1')"))->toBeNull();

    $page->navigate(route('schedules.show', $schedule))->assertSee('Install Banner Schedule');
    makeInstallable($page);

    $page->assertVisible('[data-testid="pwa-banner"]');
});

it('links to the install page from the footer', function () {
    $page = visit(route('about'));

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="footer-install-link"]')
        ->assertSeeIn('[data-testid="footer-install-link"]', '安裝 NOU 小幫手')
        ->click('[data-testid="footer-install-link"]')
        ->assertPathIs('/install');
});

it('gives iPhone visitors a button to the install guide instead of an install button', function () {
    $schedule = makeInstallBannerSchedule();

    $page = visit(route('schedules.show', $schedule))
        ->withUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1')
        ->assertSee('Install Banner Schedule');

    $page->script("localStorage.removeItem('pwa_install_banner_dismissed_v1')");
    $page->navigate(route('schedules.show', $schedule))->assertSee('Install Banner Schedule');
    $page->script("document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')?.click()");

    $page->assertVisible('[data-testid="pwa-banner-install-guide"]')
        ->assertMissing('[data-testid="pwa-banner-install"]')
        ->click('[data-testid="pwa-banner-install-guide"]')
        ->assertPathIs('/install');
});
