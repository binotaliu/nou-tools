<?php

use App\Enums\StickyNoteStatus;
use App\Models\AcornTransaction;
use App\Models\CafeOrder;
use App\Models\CafeStickyNote;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// The pad, the placement and the wall are client-side (StickyNoteCanvas.vue,
// StickyNoteComposer.vue, StickyNoteBoard.vue, StickyNoteWall.vue). The wall
// opens in a modal from the corkboard on the floor map; the composer is three
// steps (draw, place on the real wall, confirm). Strokes are synthetic pointer
// events on the canvas. The test server does not parse multipart bodies, so the
// upload itself is stubbed here and covered by StickyNoteTest (Feature).

$signedIn = function (): array {
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Browser Note Schedule']);
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '塗鴉同學']);
    AcornTransaction::factory()->for($schedule, 'schedule')->credit(10)->create();
    CafeOrder::factory()->for($schedule, 'schedule')->create();

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    waitUntil($page, 'document.cookie !== undefined', 2000);

    return [$schedule, $page];
};

it('draws a note, places it on the wall, confirms and sends a 480 square png with the text', function () use ($signedIn) {
    [, $page] = $signedIn();

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-open-note"]\') !== null');

    // The wall lives on the back wall of the floor map and opens in a modal.
    $page->click('[data-testid="cafe-wall-open"]')
        ->assertVisible('[data-testid="cafe-wall-empty"]')
        ->click('[data-testid="cafe-wall-compose"]')
        ->assertVisible('[data-testid="cafe-note-composer"]')
        ->assertMissing('[data-testid="cafe-wall"]');

    $nextDisabled = $page->script("document.querySelector('[data-testid=\"cafe-note-next\"]').disabled");
    expect($nextDisabled)->toBeTrue();

    $page->script(<<<'JS'
        (() => {
            window.axios.post = async (url, form) => {
                window.__sent = { url, text: form.get('text'), color: form.get('paperColor'), x: form.get('x'), y: form.get('y'), blob: form.get('drawing') };

                return { data: { acorns: { balance: 5, recent: [] } } };
            };
        })()
    JS);

    // The colour radios are visually hidden (sr-only) behind their swatch label.
    $page->script("document.querySelector('[data-testid=\"note-color-pink\"]').click()");
    $page->fill('[data-testid="cafe-note-text"]', '期中考加油');

    $page->script(<<<'JS'
        (() => {
            const canvas = document.querySelector('[data-testid="sticky-note-canvas"]');
            const rect = canvas.getBoundingClientRect();
            const fire = (type, x, y) => canvas.dispatchEvent(new PointerEvent(type, {
                clientX: rect.left + x, clientY: rect.top + y, pointerId: 1, bubbles: true,
            }));
            fire('pointerdown', 30, 30);
            fire('pointermove', 120, 90);
            fire('pointermove', 200, 40);
            fire('pointerup', 200, 40);
        })()
    JS);

    waitUntil($page, '!document.querySelector(\'[data-testid="sticky-note-clear"]\').disabled');

    // Step 2 shows the real wall with the draft on it; a click moves the draft.
    $page->click('[data-testid="cafe-note-next"]')
        ->assertVisible('[data-testid="cafe-note-placement"]')
        ->assertVisible('[data-testid="cafe-note-draft"]');

    $page->script(<<<'JS'
        (() => {
            const board = document.querySelector('[data-testid="cafe-note-placement"] > div');
            const rect = board.getBoundingClientRect();
            board.dispatchEvent(new MouseEvent('click', {
                clientX: rect.left + rect.width * 0.9, clientY: rect.top + rect.height * 0.9, bubbles: true,
            }));
        })()
    JS);

    // Step 3 shows it once more and carries the price.
    $page->click('[data-testid="cafe-note-place-next"]')
        ->assertVisible('[data-testid="cafe-note-preview"]')
        ->assertSeeIn('[data-testid="cafe-note-submit"]', '5')
        ->click('[data-testid="cafe-note-submit"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-note-message"]\') !== null');

    $sent = $page->script(<<<'JS'
        (async () => {
            const bitmap = await createImageBitmap(window.__sent.blob);

            return JSON.stringify({
                url: window.__sent.url,
                text: window.__sent.text,
                color: window.__sent.color,
                x: Number(window.__sent.x),
                y: Number(window.__sent.y),
                type: window.__sent.blob.type,
                width: bitmap.width,
                height: bitmap.height,
            });
        })()
    JS);

    expect(json_decode($sent, true))->toBe([
        'url' => '/cafe/notes',
        'text' => '期中考加油',
        'color' => 'pink',
        'x' => 1,
        'y' => 1,
        'type' => 'image/png',
        'width' => 480,
        'height' => 480,
    ]);

    $page->assertMissing('[data-testid="cafe-note-composer"]')
        ->assertSeeIn('[data-testid="cafe-acorns"]', '5 顆橡實');
});

it('shows published notes on the wall with their drawings and lets a reader open one', function () use ($signedIn) {
    Storage::fake('cafe_sticky_notes');
    Storage::disk('cafe_sticky_notes')->put('wall.png', UploadedFile::fake()->image('wall.png', 480, 480)->get());

    $author = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($author, 'schedule')->create(['nickname' => '牆上的同學', 'emoji' => '📚']);
    $note = CafeStickyNote::factory()->for($author, 'schedule')->create(['text' => '一起加油', 'drawing_path' => 'wall.png', 'paper_color' => 'green']);
    $held = CafeStickyNote::factory()->status(StickyNoteStatus::PendingReview)->create(['text' => '還在審核']);

    [, $page] = $signedIn();

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-wall-open"] img\') !== null');

    $page->click('[data-testid="cafe-wall-open"]')
        ->assertMissing('[data-testid="cafe-wall-empty"]')
        ->assertMissing('[data-testid="cafe-note-'.$held->id.'"]')
        ->click('[data-testid="cafe-note-'.$note->id.'"]')
        ->assertSeeIn('[data-testid="cafe-note-detail"]', '牆上的同學')
        ->assertSeeIn('[data-testid="cafe-note-detail"]', '一起加油')
        ->assertVisible('[data-testid="cafe-note-report"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-note-'.$note->id.'"] img\')?.naturalWidth === 480');

    $page->click('[data-testid="cafe-note-report"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-note-report"]\').disabled');

    expect($note->fresh()->report_count)->toBe(1);
});

it('keeps an unposted draft in local storage and restores it, and blocks drawing without a coffee today', function () use ($signedIn) {
    [$schedule, $page] = $signedIn();

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-open-note"]\') !== null');

    $page->click('[data-testid="cafe-open-note"]')
        ->assertVisible('[data-testid="cafe-note-composer"]')
        ->fill('[data-testid="cafe-note-text"]', '還沒貼的草稿');

    waitUntil($page, 'localStorage.getItem("nou:cafe:note-draft:v1") !== null');

    $page->navigate(route('cafe.show'));
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-open-note"]\') !== null');
    $page->click('[data-testid="cafe-open-note"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-note-text"]\')?.value === "還沒貼的草稿"');

    CafeOrder::query()->where('student_schedule_id', $schedule->id)->delete();

    $page->navigate(route('cafe.show'));
    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-open-note"]\') !== null');
    $page->click('[data-testid="cafe-open-note"]')
        ->assertVisible('[data-testid="cafe-note-blocked"]')
        ->assertMissing('[data-testid="cafe-note-composer"]');
});
