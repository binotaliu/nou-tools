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
// StickyNoteComposer.vue, StickyNoteWall.vue). Strokes are synthetic pointer
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

it('builds a 480 square png and the note form from a drawing and some text', function () use ($signedIn) {
    [, $page] = $signedIn();

    $page->navigate(route('cafe.show'));
    dismissCookieConsentBanner($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-open-note"]\') !== null');

    $page->assertVisible('[data-testid="cafe-wall-empty"]')
        ->click('[data-testid="cafe-open-note"]')
        ->assertVisible('[data-testid="cafe-note-composer"]');

    $submitDisabled = $page->script("document.querySelector('[data-testid=\"cafe-note-submit\"]').disabled");
    expect($submitDisabled)->toBeTrue();

    $page->script(<<<'JS'
        (() => {
            window.axios.post = async (url, form) => {
                window.__sent = { url, text: form.get('text'), color: form.get('paperColor'), blob: form.get('drawing') };

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

    $page->click('[data-testid="cafe-note-submit"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-note-message"]\') !== null');

    $sent = $page->script(<<<'JS'
        (async () => {
            const bitmap = await createImageBitmap(window.__sent.blob);

            return JSON.stringify({
                url: window.__sent.url,
                text: window.__sent.text,
                color: window.__sent.color,
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

    waitUntil($page, 'document.querySelector(\'[data-testid="cafe-note-'.$note->id.'"]\') !== null');

    $page->assertMissing('[data-testid="cafe-wall-empty"]')
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
