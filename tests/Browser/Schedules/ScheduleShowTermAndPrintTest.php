<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;
use Pest\Browser\Api\PendingAwaitablePage;

// The semester `<select>` and the print button's link are built client-side, so
// the markup alone looks correct to a server-rendered Feature test whether
// or not the listeners actually attached. This regressed exactly that way
// once before, so it's checked in a real browser.

// The remember-schedule modal (see RememberScheduleTest) only shows up when
// no `student_schedule` cookie is present, and cookie state isn't guaranteed
// to be reset between test files in the same run. click() has no bounded
// wait, so blindly dismissing a modal that might not be there can hang the
// whole suite — check for it first via script().
//
// The Inertia page only mounts the modal into the DOM once client-side
// hydration completes, so a `script()` check run immediately after `visit()`
// can race it and find nothing. Rather than guessing how long hydration
// takes, poll until either the modal shows up or the print button (always
// present once hydrated, modal or not) confirms hydration finished.
$dismissRememberModalIfPresent = function (PendingAwaitablePage $page): void {
    waitUntil(
        $page,
        'document.querySelector(\'[data-testid="remember-schedule-dismiss"]\') !== null'.
        ' || document.querySelector(\'[data-testid="schedule-print-button"]\') !== null'
    );

    if ($page->script("!!document.querySelector('[data-testid=\"remember-schedule-dismiss\"]')")) {
        $page->click('[data-testid="remember-schedule-dismiss"]');
    }
};

it('submits the term form and navigates when a different semester is selected', function () use ($dismissRememberModalIfPresent) {
    config()->set('app.current_semester', '2026C');

    $currentCourse = Course::factory()->create(['term' => '2026C']);
    $currentClass = CourseClass::factory()->create(['course_id' => $currentCourse->id]);

    $otherCourse = Course::factory()->create(['term' => '2025B']);
    $otherClass = CourseClass::factory()->create(['course_id' => $otherCourse->id]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Term Switch Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $currentCourse->id,
        'course_class_id' => $currentClass->id,
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $otherCourse->id,
        'course_class_id' => $otherClass->id,
    ]);

    $page = visit(route('schedules.show', $schedule));
    $dismissRememberModalIfPresent($page);

    // select() submits the form (@change="$event.target.form.submit()"),
    // which navigates the page. Give that navigation a moment to land before
    // reading the URL — and don't screenshot() here, since racing the
    // in-flight navigation can hang the browser driver.
    $page->select('#term', '2025B');

    waitUntil($page, "location.href.includes('term=2025B')");

    expect($page->url())->toContain('term=2025B');
});

it('offers each week start for the schedule PDF from the print menu', function () use ($dismissRememberModalIfPresent) {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Print Schedule',
    ]);

    $page = visit(route('schedules.show', ['schedule' => $schedule, 'term' => '2025B']));
    $dismissRememberModalIfPresent($page);

    $page->assertMissing('[data-testid="schedule-print-menu"]')
        ->click('[data-testid="schedule-print-button"]')
        ->assertPresent('[data-testid="schedule-print-menu"]');

    foreach (['monday', 'sunday'] as $weekStart) {
        $page->assertAttribute(
            '[data-testid="schedule-print-'.$weekStart.'"]',
            'href',
            '/schedules/'.$schedule->getRouteKey().'/print.pdf?term=2025B&week_start='.$weekStart,
        )->assertAttribute('[data-testid="schedule-print-'.$weekStart.'"]', 'target', '_blank');
    }
});

// In an installed PWA the PDF would open inside the app with no way back on
// iOS, so the print menu fetches it and hands it to the share sheet. A
// headless tab isn't standalone and can't share files, so both are stubbed
// (the head script's `html[data-pwa]` flag, `navigator.share`), and so is the
// PDF request, which is held until the test releases it to see the loading state.
$stubPwaPdfShare = function ($page, array $shareFailures = [], int $pdfStatus = 200): void {
    $failures = json_encode($shareFailures);

    $page->script(<<<JS
        (() => {
            document.documentElement.dataset.pwa = '';
            window.__shared = [];
            const failures = {$failures};
            navigator.canShare = () => true;
            navigator.share = async data => {
                if (failures.length) {
                    throw new DOMException('refused', failures.shift());
                }
                window.__shared.push(data.files[0].name + '|' + data.files[0].type);
            };
            const realFetch = window.fetch;
            window.fetch = (url, ...rest) => String(url).includes('print.pdf')
                ? new Promise(resolve => {
                    window.__releasePdf = () => resolve(new Response(new Blob(['%PDF-1.7'], { type: 'application/pdf' }), { status: {$pdfStatus} }));
                })
                : realFetch(url, ...rest);
        })()
        JS);
};

$openPrintMenuAndChoose = function ($page, string $weekStart): void {
    dismissCookieConsentBanner($page);
    $page->click('[data-testid="schedule-print-button"]')
        ->click('[data-testid="schedule-print-'.$weekStart.'"]');
};

$sharedFiles = function ($page): array {
    return json_decode($page->script('JSON.stringify(window.__shared)'), true);
};

$visitPrintableSchedule = function () use ($dismissRememberModalIfPresent): PendingAwaitablePage {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Print Schedule']);
    $page = visit(route('schedules.show', ['schedule' => $schedule, 'term' => '2025B']));
    $dismissRememberModalIfPresent($page);

    return $page;
};

it('shares the PDF from an installed PWA instead of navigating to it, showing a loading state meanwhile', function () use ($stubPwaPdfShare, $openPrintMenuAndChoose, $sharedFiles, $visitPrintableSchedule) {
    $page = $visitPrintableSchedule();
    dismissCookieConsentBanner($page);
    $stubPwaPdfShare($page);
    $url = $page->url();

    $openPrintMenuAndChoose($page, 'sunday');

    $page->assertSee('產生 PDF 中…');
    expect($page->script('document.querySelector(\'[data-testid="schedule-print-button"]\').disabled'))->toBeTrue();
    expect($sharedFiles($page))->toBe([]);

    $page->script('window.__releasePdf()');

    waitUntil($page, 'window.__shared.length > 0');

    expect($sharedFiles($page))->toBe(['nou-schedule-2025B.pdf|application/pdf'])
        ->and($page->url())->toBe($url);
    $page->assertDontSee('產生 PDF 中…')->assertSee('列印');
});

it('asks for another tap when the browser refuses to share after the wait', function () use ($stubPwaPdfShare, $openPrintMenuAndChoose, $sharedFiles, $visitPrintableSchedule) {
    $page = $visitPrintableSchedule();
    $stubPwaPdfShare($page, ['NotAllowedError']);

    $openPrintMenuAndChoose($page, 'monday');
    $page->script('window.__releasePdf()');

    waitUntil($page, "document.body.textContent.includes('分享 PDF')");

    expect($sharedFiles($page))->toBe([]);
    $page->assertSee('分享 PDF')->click('[data-testid="schedule-print-button"]');

    waitUntil($page, 'window.__shared.length > 0');

    expect($sharedFiles($page))->toBe(['nou-schedule-2025B.pdf|application/pdf']);
    $page->assertDontSee('分享 PDF')->assertSee('列印');
});

it('says so when the PDF cannot be produced', function () use ($stubPwaPdfShare, $openPrintMenuAndChoose, $sharedFiles, $visitPrintableSchedule) {
    $page = $visitPrintableSchedule();
    $stubPwaPdfShare($page, [], 500);

    $openPrintMenuAndChoose($page, 'monday');
    $page->script('window.__releasePdf()');

    waitUntil($page, 'document.querySelector(\'[data-testid="schedule-print-error"]\') !== null');

    $page->assertPresent('[data-testid="schedule-print-error"]')->assertSee('無法產生 PDF');
    expect($sharedFiles($page))->toBe([]);
});
