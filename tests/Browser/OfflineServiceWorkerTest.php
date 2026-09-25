<?php

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// The service worker (public/sw.js) intercepts navigations and swaps in the
// cached /offline page when the network response is a gateway/outage error
// (502/503/504, or Cloudflare's 520-527 origin-error range) — the case
// where the client is online but Cloudflare/the origin can't be reached.
// fetch() resolves normally for these (it only rejects on true network
// failure), so they'd otherwise slip through as Cloudflare's own error page.
//
// A real app response in that status range doesn't happen in practice, so a
// route is faked here to return a 503 the same way Cloudflare would.

it('shows the cached offline page for a gateway/outage response once the service worker controls the page', function () {
    // Three path segments so it can't be swallowed by the app's own
    // wildcard routes (e.g. /{type}, /{type}/{slug}).
    Route::get('/__browser-test__/simulated/outage', fn () => response('', 503));

    $page = visit('/');

    $page->script('navigator.serviceWorker.ready');

    $page->navigate('/__browser-test__/simulated/outage')
        ->assertSee('目前無法連線')
        ->screenshot();
});

it('leaves a real error response like a 404 untouched instead of masking it with the offline page', function () {
    $page = visit('/');

    $page->script('navigator.serviceWorker.ready');

    $page->navigate('/this-route-does-not-exist')
        ->assertDontSee('目前無法連線')
        ->screenshot();
});

// Inertia's own client-side page visits (<Link>, router.visit) are fetch()
// calls with an `X-Inertia: true` header — not a real browser navigation
// (`request.mode` is never 'navigate' for those) and not JSON, so without an
// explicit check they used to fall into the generic same-origin
// staleWhileRevalidate bucket meant for JS/CSS/image assets. That silently
// served stale props (and a stale X-Inertia-Version header, defeating
// Inertia's own version-mismatch reload) on every repeat client-side visit.
it("never caches Inertia's own client-side page-visit requests", function () {
    Route::get('/__browser-test__/simulated/inertia-visit', fn () => response(Str::random(32)));

    $page = visit('/');

    $page->script('navigator.serviceWorker.ready');

    $fetchInertiaVisit = <<<'JS'
        fetch('/__browser-test__/simulated/inertia-visit', {
            headers: { 'X-Inertia': 'true' },
        }).then(r => r.text())
        JS;

    $first = $page->script($fetchInertiaVisit);
    $second = $page->script($fetchInertiaVisit);

    expect($first)->not->toBe($second);
});

it('serves the backed-up lite schedule instead of an error page during an outage', function () {
    Route::get('/__browser-test__/simulated/lite-outage', fn () => response('', 503));

    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '停機備份課表']);

    // The worker only controls navigations after its first activation, so
    // let it install on another page before opening the schedule.
    $page = visit('/');

    $page->script('navigator.serviceWorker.ready');

    $page->navigate(route('schedules.show', $schedule, false));

    // The worker refreshes the backup in the background after the page
    // loads; wait until it has landed in the cache.
    $page->script(<<<JS
        navigator.serviceWorker.ready.then(async () => {
            for (let i = 0; i < 30; i++) {
                if (await caches.match('/schedules/{$schedule->getRouteKey()}/lite')) {
                    return true
                }

                await new Promise(resolve => setTimeout(resolve, 100))
            }

            return false
        })
        JS);

    $page->navigate('/__browser-test__/simulated/lite-outage')
        ->assertSee('停機備份課表')
        ->assertDontSee('目前無法連線 -');
});

it('serves the backup on a 500 too, e.g. when the database is down', function () {
    Route::get('/__browser-test__/simulated/server-error', fn () => response('', 500));

    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => '資料庫故障課表']);

    $page = visit('/');

    $page->script('navigator.serviceWorker.ready');

    $page->navigate(route('schedules.show', $schedule, false));

    $page->script(<<<JS
        navigator.serviceWorker.ready.then(async () => {
            for (let i = 0; i < 30; i++) {
                if (await caches.match('/schedules/{$schedule->getRouteKey()}/lite')) {
                    return true
                }

                await new Promise(resolve => setTimeout(resolve, 100))
            }

            return false
        })
        JS);

    $page->navigate('/__browser-test__/simulated/server-error')
        ->assertSee('資料庫故障課表')
        ->assertSee('網路或伺服器發生問題')
        ->assertVisible('#lite-banner');
});

it('lets a 500 through untouched when there is no backup to show', function () {
    Route::get('/__browser-test__/simulated/no-backup-error', fn () => response('app broke', 500));

    $page = visit('/');

    $page->script('navigator.serviceWorker.ready');

    $page->navigate('/__browser-test__/simulated/no-backup-error')
        ->assertSee('app broke')
        ->assertDontSee('目前無法連線');
});
