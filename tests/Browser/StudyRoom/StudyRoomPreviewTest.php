<?php

// What a visitor without a schedule sees on /study-room: the room filled with
// fictional occupants (resources/js/Composables/useStudyRoomDemo.js), a banner
// pointing at schedule creation / recovery, and no live state.

it('previews the room with fictional occupants and no live state for a visitor without a schedule', function () {
    $page = visit(route('study-room.show'));

    $page->assertVisible('[data-testid="study-room-needs-schedule"]')
        ->assertVisible('[data-testid="study-room-floor-1"]')
        ->assertVisible('[data-testid="study-room-solo-seats"]')
        ->assertVisible('[data-testid="study-room-guest-card"]')
        ->assertMissing('[data-testid="study-room-personal-info"]');

    $requestedState = $page->script(
        "performance.getEntriesByType('resource').some(entry => entry.name.includes('/study-room/state'))"
    );
    $occupied = $page->script(
        "document.querySelectorAll('[data-testid^=\"seat-\"][aria-label*=\"正在使用\"]').length"
    );
    $free = $page->script(
        "document.querySelectorAll('[data-testid^=\"seat-\"][aria-label*=\"空位\"]').length"
    );

    expect($requestedState)->toBeFalse()
        ->and($occupied)->toBeGreaterThan(0)
        ->and($free)->toBeGreaterThan(0);
});

it('links the preview banner to schedule creation and recovery', function () {
    $page = visit(route('study-room.show'));

    $createHref = $page->script(
        "document.querySelector('[data-testid=\"study-room-create-schedule\"]').getAttribute('href')"
    );
    $findHref = $page->script(
        "document.querySelector('[data-testid=\"study-room-find-schedule\"]').getAttribute('href')"
    );

    expect($createHref)->toBe('/schedules/create')
        ->and($findHref)->toBe('/schedules/my');
});

it('does not try to take a seat when a visitor clicks a free one in the preview', function () {
    $page = visit(route('study-room.show'));

    $page->assertVisible('[data-testid="study-room-floor-1"]')
        ->click('[data-testid="seat-1-S03"]');

    $tookSeat = $page->script(
        "performance.getEntriesByType('resource').some(entry => entry.name.includes('/take'))"
    );

    expect($tookSeat)->toBeFalse();
    $page->assertMissing('[data-testid="study-room-personal-info-modal"]');
});
