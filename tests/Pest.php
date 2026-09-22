<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\Expectations\Targeted;
use Pest\Arch\Objects\ObjectDescription;
use Pest\Arch\Support\FileLineFinder;
use Pest\Browser\Playwright\Playwright;
use Pest\Support\Reflection;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Skip Vite Manifest Lookup In Feature Tests
|--------------------------------------------------------------------------
|
| Feature tests never build front-end assets, so any view using @vite would
| throw a ViteManifestNotFoundException. Browser tests render pages through
| a real in-process HTTP kernel (see LaravelHttpServer::handleRequest) that
| shares this container, so they still need real built assets and must not
| have Vite swapped out here.
|
*/

uses()->beforeEach(function (): void {
    $this->withoutVite();
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Browser Tests - Chrome Process Cleanup
|--------------------------------------------------------------------------
|
| pest-plugin-browser doesn't close the underlying Chrome process between
| tests, so it accumulates one Chrome process per test across a run. Under
| --parallel this exhausts CPU/RAM within a few dozen tests, and the
| resulting contention is what looks like a "hung" browser test rather than
| a slow one. Force-closing after every test keeps exactly one Chrome
| process alive per worker.
| @see https://github.com/pestphp/pest/issues/1480
|
*/

uses()->afterAll(function (): void {
    Playwright::close();
})->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toHaveOnlyCamelCasePublicProperties', function (): ArchExpectation {
    return Targeted::make(
        $this,
        fn (ObjectDescription $object): bool => isset($object->reflectionClass) === false
            || array_filter(
                Reflection::getPropertiesFromReflectionClass($object->reflectionClass),
                fn (ReflectionProperty $property): bool => $property->isPublic()
                    && preg_match('/^[a-z]+([A-Z][a-z0-9]+)*$/', $property->name) !== 1,
            ) === [],
        'to have only camelCase public properties',
        FileLineFinder::where(fn (string $line): bool => str_contains($line, 'class'))
    );
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Mirrors window.NouTime.gmtLabel() from resources/js/app.js, for asserting
 * the client-rendered "your time" hint in browser tests.
 */
function gmtLabelForOffset(int $offsetMinutes): string
{
    $sign = $offsetMinutes >= 0 ? '+' : '-';
    $hours = intdiv(abs($offsetMinutes), 60);
    $minutes = abs($offsetMinutes) % 60;

    return 'GMT'.$sign.$hours.($minutes ? ':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');
}

/**
 * Mirrors window.NouTime.WEEKDAYS, for asserting client-rendered dates.
 */
function chineseWeekdayChar(Carbon $date): string
{
    return ['日', '一', '二', '三', '四', '五', '六'][(int) $date->format('w')];
}

/**
 * The cookie-consent banner is fixed to the viewport bottom and, until a
 * choice is made, can overlap other fixed/bottom-of-page controls that
 * browser tests click through. Dismissed via JS rather than click() since
 * it may not be showing at all (an explicit choice was already made, or
 * the resolved country doesn't require one), in which case this polls
 * briefly then gives up rather than blocking. The poll (rather than a
 * single querySelector) is needed because this runs right after visit(),
 * before Vue has necessarily hydrated the banner in.
 */
function dismissCookieConsentBanner(mixed $page): mixed
{
    $page->script(<<<'JS'
        new Promise((resolve) => {
            const deadline = Date.now() + 2000;

            (function tryClick() {
                const button = document.querySelector('[data-testid="cookie-consent-accept"]');

                if (button) {
                    button.click();

                    return resolve();
                }

                if (Date.now() > deadline) {
                    return resolve();
                }

                setTimeout(tryClick, 100);
            })();
        })
        JS);

    return $page;
}
