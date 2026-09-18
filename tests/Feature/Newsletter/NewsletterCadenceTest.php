<?php

use NouTools\Domains\Newsletter\Schedule\NewsletterCadence;

beforeEach(function () {
    config(['newsletter.anchor_date' => '2026-09-21', 'newsletter.cadence_days' => 14]);
});

it('resolves the anchor issue and its windows', function () {
    $schedule = app(NewsletterCadence::class)->forPublishDate('2026-09-21');

    expect($schedule->issueKey)->toBe('2026-W39')
        ->and($schedule->editingStartsOn->toDateString())->toBe('2026-09-14')
        ->and($schedule->coversFrom->toDateString())->toBe('2026-09-07')
        ->and($schedule->coversTo->toDateString())->toBe('2026-09-20')
        ->and($schedule->highlightsFrom->toDateString())->toBe('2026-09-21')
        ->and($schedule->highlightsTo->toDateString())->toBe('2026-10-04');
});

it('finds the next issue on or after a date', function (string $date, string $expectedKey) {
    expect(app(NewsletterCadence::class)->nextOnOrAfter($date)->issueKey)->toBe($expectedKey);
})->with([
    'before the anchor' => ['2026-09-01', '2026-W39'],
    'on the anchor' => ['2026-09-21', '2026-W39'],
    'day after the anchor' => ['2026-09-22', '2026-W41'],
    'on the next issue' => ['2026-10-05', '2026-W41'],
]);

it('keeps a 14-day cadence across a 53-week ISO year', function () {
    $cadence = app(NewsletterCadence::class);

    $lastOf2026 = $cadence->forPublishDate('2026-12-28');
    $firstOf2027 = $cadence->nextOnOrAfter('2026-12-29');

    expect($lastOf2026->issueKey)->toBe('2026-W53')
        ->and($firstOf2027->issueKey)->toBe('2027-W02')
        ->and($firstOf2027->publishesOn->toDateString())->toBe('2027-01-11');
});

it('resolves an issue from its key', function () {
    $schedule = app(NewsletterCadence::class)->forIssueKey('2027-W02');

    expect($schedule->publishesOn->toDateString())->toBe('2027-01-11');
});

it('rejects off-cadence dates and keys', function (string $input) {
    $cadence = app(NewsletterCadence::class);

    str_contains($input, 'W') ? $cadence->forIssueKey($input) : $cadence->forPublishDate($input);
})->with([
    'odd-cadence Monday' => ['2026-09-28'],
    'not a Monday' => ['2026-09-22'],
    'before the anchor' => ['2026-09-07'],
    'off-cadence key' => ['2026-W40'],
    'malformed key' => ['2026-W9'],
    'week out of range' => ['2026-W60'],
])->throws(InvalidArgumentException::class);

it('knows which issue starts its editing week on a Monday', function () {
    $cadence = app(NewsletterCadence::class);

    expect($cadence->startingEditingOn('2026-09-28')?->issueKey)->toBe('2026-W41')
        ->and($cadence->startingEditingOn('2026-10-05'))->toBeNull();
});
