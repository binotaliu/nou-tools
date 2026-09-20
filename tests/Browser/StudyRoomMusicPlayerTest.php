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
function studyRoomPlaylist(string $title, array $titles): MusicPlaylist
{
    $playlist = MusicPlaylist::factory()->create(['title' => $title]);

    foreach ($titles as $position => $trackTitle) {
        MusicPlaylistItem::factory()
            ->for($playlist, 'playlist')
            ->for(MusicTrack::factory()->create(['title' => $trackTitle]), 'track')
            ->create(['position' => $position]);
    }

    return $playlist;
}

/**
 * Remembers a fresh schedule, sets a profile and lands in the study room.
 */
function openStudyRoomForMusic(): mixed
{
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
        ->waitForEvent('load');

    $page->navigate(route('study-room.show'))
        ->assertVisible('[data-testid="study-room-profile-form"]')
        ->fill('nickname', '聽音樂的人')
        ->click('[data-testid="study-room-emoji-choices"] label:nth-child(1)')
        ->click('[data-testid="study-room-profile-submit"]')
        ->wait(1);

    $page->assertVisible('[data-testid="study-room-root"]');

    return $page;
}

/**
 * Stops the page's <audio> from touching the network or a real decoder.
 */
function stubMediaElement(mixed $page): void
{
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
}

it('leaves the wall without a player while no playlists exist', function () {
    $page = openStudyRoomForMusic();

    $page->assertVisible('[data-testid="study-room-clock"]')
        ->assertMissing('[data-testid="study-room-music-player"]');
});

it('plays a tape, skips tracks, and swaps playlists from the shelf', function () {
    $rain = studyRoomPlaylist('雨聲', ['Rain Tape', 'Cafe Tape']);
    $night = studyRoomPlaylist('夜間', ['Night Tape']);

    $page = openStudyRoomForMusic();
    stubMediaElement($page);

    $page->wait(1)
        ->assertVisible('[data-testid="study-room-music-player"]')
        ->assertVisible('[data-testid="study-room-music-empty"]')
        ->assertMissing('[data-testid="study-room-music-cassette"]')
        ->assertAttribute('[data-testid="study-room-music-play"]', 'aria-pressed', 'false');

    // Pressing play slides the cassette in and starts the first track.
    $page->click('[data-testid="study-room-music-play"]')
        ->wait(1)
        ->assertVisible('[data-testid="study-room-music-cassette"]')
        ->assertMissing('[data-testid="study-room-music-empty"]')
        ->assertAttribute('[data-testid="study-room-music-play"]', 'aria-pressed', 'true')
        ->assertSeeIn('[data-testid="study-room-music-title"]', 'Rain Tape');

    $page->click('[data-testid="study-room-music-next"]')
        ->wait(1)
        ->assertSeeIn('[data-testid="study-room-music-title"]', 'Cafe Tape');

    // Pausing keeps the tape in the deck.
    $page->click('[data-testid="study-room-music-play"]')
        ->wait(1)
        ->assertAttribute('[data-testid="study-room-music-play"]', 'aria-pressed', 'false')
        ->assertVisible('[data-testid="study-room-music-cassette"]');

    // Ejecting takes it out and offers the other playlists.
    $page->click('[data-testid="study-room-music-eject"]')
        ->wait(1)
        ->assertMissing('[data-testid="study-room-music-cassette"]')
        ->assertVisible('[data-testid="study-room-music-shelf"]')
        ->assertVisible('[data-testid="study-room-music-playlist-'.$rain->id.'"]');

    $page->click('[data-testid="study-room-music-playlist-'.$night->id.'"]')
        ->wait(1)
        ->assertMissing('[data-testid="study-room-music-shelf"]')
        ->assertVisible('[data-testid="study-room-music-cassette"]')
        ->assertSeeIn('[data-testid="study-room-music-title"]', 'Night Tape');
});

it('saves the volume the listener picks', function () {
    studyRoomPlaylist('雨聲', ['Rain Tape']);

    $page = openStudyRoomForMusic();

    $page->wait(1)->assertVisible('[data-testid="study-room-music-volume"]');

    $page->script(<<<'JS'
        const slider = document.querySelector('[data-testid="study-room-music-volume"]')
        slider.value = '0.25'
        slider.dispatchEvent(new Event('input', { bubbles: true }))
    JS);

    expect($page->script("localStorage.getItem('nou:study-room:music-volume:v1')"))->toBe('0.25');
});

it('puts the player left of the clock on a phone and under the window on a desktop', function () {
    studyRoomPlaylist('雨聲', ['Rain Tape']);

    $page = openStudyRoomForMusic();

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

    $page->resize(...MUSIC_PHONE)->wait(1);
    $phone = $page->script($rects);

    expect($phone['player']['right'])->toBeLessThanOrEqual($phone['clock']['left'])
        // Same row: the two boxes overlap vertically.
        ->and($phone['player']['top'])->toBeLessThan($phone['clock']['bottom'])
        ->and($phone['clock']['top'])->toBeLessThan($phone['player']['bottom'])
        // ...and that row is below the window.
        ->and($phone['player']['top'])->toBeGreaterThanOrEqual($phone['garden']['bottom']);

    $page->resize(...MUSIC_DESKTOP)->wait(1);
    $desktop = $page->script($rects);

    expect($desktop['player']['top'])->toBeGreaterThanOrEqual($desktop['garden']['bottom'])
        // The garden sits inside the window frame's 4px border.
        ->and(abs($desktop['player']['left'] - $desktop['garden']['left']))->toBeLessThanOrEqual(4.5)
        ->and($desktop['clock']['left'])->toBeGreaterThanOrEqual($desktop['player']['right']);
});
