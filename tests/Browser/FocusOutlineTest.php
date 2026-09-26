<?php

use App\Enums\DiscountStoreStatus;
use App\Models\Announcement;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\DiscountStore;
use App\Models\LearningProgress;
use App\Models\StudentSchedule;

// The global :focus-visible outline is drawn outside the element, so any
// ancestor that clips overflow (overflow-hidden, scroll containers) can cut it
// off. This focuses every visible control and checks that the outline's outer
// edge stays inside every clipping ancestor. Fix offenders with
// `focus-visible:-outline-offset-2` (draw inside), `ring-inset` or padding.

/**
 * @return array<int, string> descriptions of controls whose outline is clipped
 */
function clippedFocusOutlines($page): array
{
    return json_decode($page->script(<<<'JS'
JSON.stringify((() => {
  const bad = [];
  const sel = 'a[href], button, input:not([type=hidden]), select, textarea, summary, [tabindex]:not([tabindex="-1"])';
  for (const el of document.querySelectorAll(sel)) {
    if (el.closest('[aria-hidden="true"], .sr-only, .skip-link, .leaflet-container, [hidden], [inert]')) continue;
    if (el.disabled || !el.getClientRects().length) continue;
    const cs0 = getComputedStyle(el);
    if (cs0.visibility === 'hidden' || cs0.position === 'fixed') continue;
    el.focus({ preventScroll: false });
    if (document.activeElement !== el || !el.matches(':focus-visible')) continue;
    const cs = getComputedStyle(el);
    if (cs.outlineStyle === 'none' || parseFloat(cs.outlineWidth) === 0) continue;
    const grow = parseFloat(cs.outlineOffset) + parseFloat(cs.outlineWidth);
    const r = el.getBoundingClientRect();
    const out = { l: r.left - grow, t: r.top - grow, r: r.right + grow, b: r.bottom + grow };
    let containing = cs.position === 'absolute' ? el.offsetParent : null;
    let skipping = !!containing;
    for (let a = el.parentElement; a && a !== document.documentElement; a = a.parentElement) {
      if (skipping) { if (a === containing) skipping = false; else continue; }
      const s = getComputedStyle(a);
      const clipX = s.overflowX !== 'visible', clipY = s.overflowY !== 'visible';
      if (!clipX && !clipY) continue;
      const ar = a.getBoundingClientRect();
      const l = ar.left + a.clientLeft, t = ar.top + a.clientTop;
      const rr = l + a.clientWidth, bb = t + a.clientHeight;
      const eps = 0.5;
      const cutX = clipX && (out.l < l - eps || out.r > rr + eps);
      const cutY = clipY && (out.t < t - eps || out.b > bb + eps);
      if (cutX || cutY) {
        bad.push(`${el.tagName}${el.dataset.testid ? '[' + el.dataset.testid + ']' : ''} "${(el.getAttribute('aria-label') || el.textContent).trim().slice(0, 20)}" clipped by ${a.tagName}.${String(a.className).split(/\s+/).slice(0, 4).join('.')}`);
        break;
      }
    }
  }
  return bad;
})())
JS), true);
}

it('does not clip the focus outline of video class links', function () {
    $course = Course::factory()->create(['name' => '焦點測試課']);
    $class = CourseClass::factory()->for($course)->create([
        'start_time' => '19:00',
        'end_time' => '20:50',
        'link' => 'https://example.com/room',
        'backup_classroom_url' => 'https://example.com/backup',
    ]);
    ClassSchedule::factory()->for($class, 'courseClass')->create(['date' => '2030-09-30']);

    $page = visit(route('video-classes.index', ['date' => '2030-09-30']))->resize(1280, 900);
    $page->assertNoJavaScriptErrors();
    $page->assertSee('焦點測試課');

    expect(clippedFocusOutlines($page))->toBe([]);
});

it('does not clip the focus outline on public pages', function (string $url) {
    $page = visit($url)->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->assertPresent('main#main-content');

    expect(clippedFocusOutlines($page))->toBe([]);
})->with([
    'home' => '/',
    'schedule find' => '/schedules/my',
    'about' => '/about',
    'settings' => '/settings',
    'article list' => '/kb',
    'announcements' => '/announcements',
    'discount stores' => '/discount-stores',
    'study room preview' => '/study-room',
]);

it('does not clip the focus outline on pages with data', function (string $page) {
    config()->set('app.current_semester', '2025B');
    config()->set('app.current_semester_range', ['2026-02-23', '2026-06-28']);

    $courseClass = CourseClass::factory()
        ->for(Course::factory()->state([
            'term' => '2025B',
            'final_date' => '2026-06-27',
            'exam_time_start' => '15:00',
            'exam_time_end' => '16:10',
        ]))
        ->create();
    ClassSchedule::factory()->for($courseClass, 'courseClass')->create(['date' => '2026-03-05']);

    $schedule = StudentSchedule::factory()->create();
    $schedule->items()->create([
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);
    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
        'progress' => [],
        'notes' => [],
    ]);
    Announcement::factory()->count(3)->create();
    DiscountStore::factory()->count(3)->create(['status' => DiscountStoreStatus::Online]);

    $url = match ($page) {
        'schedule' => route('schedules.show', $schedule),
        'schedule editor' => route('schedules.edit', $schedule),
        'learning progress' => route('learning-progress.show', [$schedule, '2025B']),
        'course' => route('course.show', $courseClass->course),
        'course schedule' => route('course.schedule'),
        'directory' => route('directory.index'),
        'alt-uu' => route('alt-uu'),
        'newsletter' => route('newsletter.index'),
        'announcements' => route('announcements.index'),
        'discount stores' => route('discount-stores.index'),
    };

    $visit = visit($url)->resize(1280, 900);
    $visit->assertNoJavaScriptErrors()->assertPresent('main#main-content');

    expect(clippedFocusOutlines($visit))->toBe([]);
})->with(['schedule', 'schedule editor', 'learning progress', 'course', 'course schedule', 'directory', 'alt-uu', 'newsletter', 'announcements', 'discount stores']);

it('does not clip the focus outline in the study room list view', function () {
    $page = visit('/study-room')->resize(1280, 900);
    $page->assertNoJavaScriptErrors()->click('[data-testid="study-room-view-list"]');
    $page->assertPresent('[data-testid="study-room-seat-list"] tbody th');

    expect(clippedFocusOutlines($page))->toBe([]);
});
