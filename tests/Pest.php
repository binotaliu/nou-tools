<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\Expectations\Targeted;
use Pest\Arch\Objects\ObjectDescription;
use Pest\Arch\Support\FileLineFinder;
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
