<?php

use App\Models\Course;
use App\Models\MusicPlaylist;
use App\Models\MusicPlaylistItem;
use App\Models\MusicTrack;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Str;

// The cassette player on the study room Wall
// (resources/js/Components/StudyRoom/CassettePlayer.vue, driven by
// resources/js/Composables/useStudyRoomMusic.js). Real audio files don't
// exist here, so each test stubs the media element in the page first; what's
// asserted is the deck's own state and where it sits on the Wall.

const MUSIC_PHONE = [390, 844];
const MUSIC_DESKTOP = [1280, 900];

/**
 * @param  array<int, string>  $titles
 */
$studyRoomPlaylist = function (string $title, array $titles): MusicPlaylist {
    $playlist = MusicPlaylist::factory()->create(['title' => $title]);

    foreach ($titles as $position => $trackTitle) {
        MusicPlaylistItem::factory()
            ->for($playlist, 'playlist')
            ->for(MusicTrack::factory()->create(['title' => $trackTitle]), 'track')
            ->create(['position' => $position]);
    }

    return $playlist;
};

/**
 * Remembers a fresh schedule, sets a profile and lands in the study room.
 */
$openStudyRoomForMusic = function (): mixed {
    $course = Course::factory()->create(['term' => config('app.current_semester')]);
    $schedule = StudentSchedule::create(['uuid' => Str::uuid(), 'name' => 'Music Schedule']);

    StudentScheduleItem::query()->create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $course->id,
    ]);

    $page = visit(route('schedules.show', $schedule));
    $page->script('navigator.serviceWorker.ready');

    $page->assertVisible('[data-testid="remember-schedule-modal"]')
        ->click('[data-testid="remember-schedule-confirm"]')
        ->assertMissing('[data-testid="remember-schedule-modal"]');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '聽音樂的人')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-root"]\') !== null');

    dismissCookieConsentBanner($page);

    $page->assertVisible('[data-testid="study-room-root"]');

    return $page;
};

/**
 * Stops the page's <audio> from touching the network or a real decoder.
 */
$stubMediaElement = function (mixed $page): void {
    $page->script(<<<'JS'
        HTMLMediaElement.prototype.play = () => Promise.resolve()
        HTMLMediaElement.prototype.pause = () => {}
        HTMLMediaElement.prototype.load = () => {}
        Object.defineProperty(HTMLMediaElement.prototype, 'src', {
            configurable: true,
            get() { return this._src || '' },
            set(value) { this._src = value },
        })
    JS);
};

it('leaves the wall without a player while no playlists exist', function () use ($openStudyRoomForMusic) {
    $page = $openStudyRoomForMusic();

    $page->assertVisible('[data-testid="study-room-clock"]')
        ->assertMissing('[data-testid="study-room-music-player"]');
});

it('plays a tape, skips tracks, and swaps playlists from the popover', function () use ($studyRoomPlaylist, $openStudyRoomForMusic, $stubMediaElement) {
    $rain = $studyRoomPlaylist('雨聲', ['Rain Tape', 'Cafe Tape']);
    $night = $studyRoomPlaylist('夜間', ['Night Tape']);

    $page = $openStudyRoomForMusic();
    $stubMediaElement($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-player"]\') !== null');

    $page->assertVisible('[data-testid="study-room-music-player"]')
        ->assertVisible('[data-testid="study-room-music-empty"]')
        ->assertMissing('[data-testid="study-room-music-cassette"]')
        ->assertAttribute('[data-testid="study-room-music-play"]', 'aria-pressed', 'false');

    // Pressing play slides the cassette in and starts the first track.
    $page->click('[data-testid="study-room-music-play"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-cassette"]\') !== null');

    $page->assertVisible('[data-testid="study-room-music-cassette"]')
        ->assertMissing('[data-testid="study-room-music-empty"]')
        ->assertAttribute('[data-testid="study-room-music-play"]', 'aria-pressed', 'true')
        ->assertSeeIn('[data-testid="study-room-music-title"]', 'Rain Tape');

    $page->click('[data-testid="study-room-music-next"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-title"]\')?.textContent.includes(\'Cafe Tape\')');

    $page->assertSeeIn('[data-testid="study-room-music-title"]', 'Cafe Tape');

    // Pausing keeps the tape in the deck.
    $page->click('[data-testid="study-room-music-play"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-play"]\')?.getAttribute(\'aria-pressed\') === \'false\'');

    $page->assertAttribute('[data-testid="study-room-music-play"]', 'aria-pressed', 'false')
        ->assertVisible('[data-testid="study-room-music-cassette"]');

    // The popover holds the playlists and the less-used controls.
    $page->assertMissing('[data-testid="study-room-music-popover"]')
        ->click('[data-testid="study-room-music-toggle"]')
        ->assertVisible('[data-testid="study-room-music-popover"]')
        ->assertVisible('[data-testid="study-room-music-playlist-'.$rain->id.'"]')
        ->assertVisible('[data-testid="study-room-music-credit"]');

    // Ejecting takes the cassette out of the deck.
    $page->click('[data-testid="study-room-music-eject"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-cassette"]\') === null');

    $page->assertMissing('[data-testid="study-room-music-cassette"]')
        ->assertVisible('[data-testid="study-room-music-empty"]');

    $page->click('[data-testid="study-room-music-playlist-'.$night->id.'"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-title"]\')?.textContent.includes(\'Night Tape\')');

    $page->assertMissing('[data-testid="study-room-music-popover"]')
        ->assertVisible('[data-testid="study-room-music-cassette"]')
        ->assertSeeIn('[data-testid="study-room-music-title"]', 'Night Tape');

    // Tapping elsewhere on the wall closes the popover again.
    $page->click('[data-testid="study-room-music-toggle"]')
        ->assertVisible('[data-testid="study-room-music-popover"]')
        ->click('[data-testid="study-room-clock"]')
        ->assertMissing('[data-testid="study-room-music-popover"]');
});

it('scrolls a track title that does not fit, and leaves a short one still', function () use ($studyRoomPlaylist, $openStudyRoomForMusic, $stubMediaElement) {
    $studyRoomPlaylist('長短', [
        'Short',
        'An extremely long track title that can never fit inside the strip',
    ]);

    $page = $openStudyRoomForMusic();
    $stubMediaElement($page);

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-player"]\') !== null');

    $page->click('[data-testid="study-room-music-play"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-title"]\')?.textContent.includes(\'Short\')');

    $page->assertSeeIn('[data-testid="study-room-music-title"]', 'Short')
        ->assertMissing('[data-testid="study-room-marquee"]');

    $page->click('[data-testid="study-room-music-next"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-marquee"]\') !== null');

    $page->assertVisible('[data-testid="study-room-marquee"]');

    expect($page->script("getComputedStyle(document.querySelector('[data-testid=\"study-room-marquee\"]')).animationName"))
        ->toBe('marquee');

    // Back to a title that fits: the scrolling copy goes away.
    $page->click('[data-testid="study-room-music-next"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-marquee"]\') === null');

    $page->assertMissing('[data-testid="study-room-marquee"]');
});

it('saves the volume the listener picks', function () use ($studyRoomPlaylist, $openStudyRoomForMusic) {
    $studyRoomPlaylist('雨聲', ['Rain Tape']);

    $page = $openStudyRoomForMusic();

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-toggle"]\') !== null');

    $page->click('[data-testid="study-room-music-toggle"]')
        ->assertVisible('[data-testid="study-room-music-volume"]');

    $page->script(<<<'JS'
        const slider = document.querySelector('[data-testid="study-room-music-volume"]')
        slider.value = '0.25'
        slider.dispatchEvent(new Event('input', { bubbles: true }))
    JS);

    expect($page->script("localStorage.getItem('nou:study-room:music-volume:v1')"))->toBe('0.25');
});

it('puts the player left of the clock on a phone and under the window on a desktop', function () use ($studyRoomPlaylist, $openStudyRoomForMusic) {
    $studyRoomPlaylist('雨聲', ['Rain Tape']);

    $page = $openStudyRoomForMusic();

    $rects = <<<'JS'
        (() => {
            const rect = selector => document.querySelector(selector).getBoundingClientRect()
            const player = rect('[data-testid="study-room-music-player"]')
            const clock = rect('[data-testid="study-room-clock"]')
            const garden = rect('[data-testid="study-room-garden"]')

            return {
                player: { left: player.left, right: player.right, top: player.top, bottom: player.bottom },
                clock: { left: clock.left, right: clock.right, top: clock.top, bottom: clock.bottom },
                garden: { left: garden.left, bottom: garden.bottom },
            }
        })()
    JS;

    $page->resize(...MUSIC_PHONE);
    waitUntil($page, 'innerWidth === '.MUSIC_PHONE[0]);
    $phone = $page->script($rects);

    // Width is tight beside the clock, so the strip drops its next button
    // (the popover still has one).
    $page->assertMissing('[data-testid="study-room-music-next"]');

    expect($phone['player']['right'])->toBeLessThanOrEqual($phone['clock']['left'])
        // Same row: the two boxes overlap vertically.
        ->and($phone['player']['top'])->toBeLessThan($phone['clock']['bottom'])
        ->and($phone['clock']['top'])->toBeLessThan($phone['player']['bottom'])
        // ...and that row is below the window.
        ->and($phone['player']['top'])->toBeGreaterThanOrEqual($phone['garden']['bottom']);

    $page->resize(...MUSIC_DESKTOP);
    waitUntil($page, 'innerWidth === '.MUSIC_DESKTOP[0]);
    $desktop = $page->script($rects);

    expect($desktop['player']['top'])->toBeGreaterThanOrEqual($desktop['garden']['bottom'])
        // The garden sits inside the window frame's 4px border.
        ->and(abs($desktop['player']['left'] - $desktop['garden']['left']))->toBeLessThanOrEqual(4.5)
        ->and($desktop['clock']['left'])->toBeGreaterThanOrEqual($desktop['player']['right']);
});

it('keeps the tape playing on the desk in focus mode, and Esc closes the popover before the fullscreen', function () use ($studyRoomPlaylist, $openStudyRoomForMusic, $stubMediaElement) {
    $studyRoomPlaylist('雨聲', ['Rain Tape', 'Cafe Tape']);

    $page = $openStudyRoomForMusic();
    $stubMediaElement($page);

    $focus = '[data-testid="study-room-focus-mode"] ';

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-player"]\') !== null');

    $page->click('[data-testid="study-room-music-play"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-music-cassette"]\') !== null');

    $page->click('[data-testid="seat-1-S01"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-start-timer"]\') !== null');

    $page->click('[data-testid="study-room-start-timer"]');

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-focus-mode-open"]\') !== null');

    $page->click('[data-testid="study-room-focus-mode-open"]');

    waitUntil($page, 'document.querySelector(\''.$focus.'[data-testid="study-room-music-player"]\') !== null');

    $page->assertVisible($focus.'[data-testid="study-room-music-player"]')
        // Same playback state as the Wall's deck: the tape is already in.
        ->assertVisible($focus.'[data-testid="study-room-music-cassette"]')
        ->assertAttribute($focus.'[data-testid="study-room-music-play"]', 'aria-pressed', 'true')
        ->assertSeeIn($focus.'[data-testid="study-room-music-title"]', 'Rain Tape')
        ->screenshot(filename: 'study-room-focus-mode-music');

    $page->click($focus.'[data-testid="study-room-music-next"]');

    waitUntil($page, 'document.querySelector(\''.$focus.'[data-testid="study-room-music-title"]\')?.textContent.includes(\'Cafe Tape\')');

    $page->assertSeeIn($focus.'[data-testid="study-room-music-title"]', 'Cafe Tape');

    // The first Esc only closes the popover; the second leaves focus mode.
    $page->click($focus.'[data-testid="study-room-music-toggle"]')
        ->assertVisible($focus.'[data-testid="study-room-music-popover"]')
        ->keys($focus, ['Escape']);

    waitUntil($page, 'document.querySelector(\''.$focus.'[data-testid="study-room-music-popover"]\') === null');

    $page->assertMissing($focus.'[data-testid="study-room-music-popover"]')
        ->assertVisible('[data-testid="study-room-focus-mode"]')
        ->keys($focus, ['Escape']);

    waitUntil($page, 'document.querySelector(\'[data-testid="study-room-focus-mode"]\') === null');

    $page->assertMissing('[data-testid="study-room-focus-mode"]');

    // Back on the Wall the same tape is still playing.
    $page->assertAttribute('[data-testid="study-room-music-play"]', 'aria-pressed', 'true')
        ->assertSeeIn('[data-testid="study-room-music-title"]', 'Cafe Tape');
});
