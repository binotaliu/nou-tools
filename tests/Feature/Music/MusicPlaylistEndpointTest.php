<?php

declare(strict_types=1);

use App\Models\MusicPlaylist;
use App\Models\MusicPlaylistItem;
use App\Models\MusicTrack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;

beforeEach(function () {
    Storage::fake(MusicTrack::AUDIO_DISK);
    Storage::fake(MusicPlaylist::COVER_DISK);
});

/**
 * @param  array<int, MusicTrack>  $tracks
 */
function playlistWith(array $tracks, array $attributes = []): MusicPlaylist
{
    $playlist = MusicPlaylist::factory()->create($attributes);

    foreach ($tracks as $position => $track) {
        MusicPlaylistItem::factory()->for($playlist, 'playlist')->for($track, 'track')->create(['position' => $position]);
    }

    return $playlist;
}

it('returns playlists with their tracks in playback order', function () {
    $first = MusicTrack::factory()->create(['title' => 'First', 'duration_seconds' => 100]);
    $second = MusicTrack::factory()->create(['title' => 'Second', 'duration_seconds' => 200, 'license_url' => null, 'source_url' => null]);
    $playlist = playlistWith([$second, $first], ['title' => 'Rainy Focus', 'description' => 'Soft rain.', 'cover_image' => 'cover.jpg']);

    getJson(route('study-room.music.playlists'))
        ->assertOk()
        ->assertJsonCount(1, 'playlists')
        ->assertJsonPath('playlists.0.id', $playlist->id)
        ->assertJsonPath('playlists.0.title', 'Rainy Focus')
        ->assertJsonPath('playlists.0.description', 'Soft rain.')
        ->assertJsonPath('playlists.0.coverImageUrl', Storage::disk(MusicPlaylist::COVER_DISK)->url('cover.jpg'))
        ->assertJsonPath('playlists.0.trackCount', 2)
        ->assertJsonPath('playlists.0.totalDurationSeconds', 300)
        ->assertJsonPath('playlists.0.tracks.0.title', 'Second')
        ->assertJsonPath('playlists.0.tracks.0.licenseUrl', null)
        ->assertJsonPath('playlists.0.tracks.0.sourceUrl', null)
        ->assertJsonPath('playlists.0.tracks.1.title', 'First')
        ->assertJsonPath('playlists.0.tracks.1.author', $first->author)
        ->assertJsonPath('playlists.0.tracks.1.license', 'CC BY 4.0')
        ->assertJsonPath('playlists.0.tracks.1.durationSeconds', 100)
        ->assertJsonPath('playlists.0.tracks.1.audioMp3Url', Storage::disk(MusicTrack::AUDIO_DISK)->url($first->mp3_path))
        ->assertJsonPath('playlists.0.tracks.1.audioOggUrl', Storage::disk(MusicTrack::AUDIO_DISK)->url($first->ogg_path));
});

it('has a null cover url when the playlist has no cover', function () {
    playlistWith([MusicTrack::factory()->create()], ['cover_image' => null]);

    getJson(route('study-room.music.playlists'))->assertJsonPath('playlists.0.coverImageUrl', null);
});

it('skips playlists without tracks', function () {
    MusicPlaylist::factory()->create();

    getJson(route('study-room.music.playlists'))
        ->assertOk()
        ->assertExactJson(['playlists' => []]);
});

it('lists a track that appears in several playlists under each of them', function () {
    $track = MusicTrack::factory()->create();
    playlistWith([$track]);
    playlistWith([$track]);

    getJson(route('study-room.music.playlists'))
        ->assertJsonCount(2, 'playlists')
        ->assertJsonPath('playlists.0.tracks.0.id', $track->id)
        ->assertJsonPath('playlists.1.tracks.0.id', $track->id);
});

it('does not run more queries as playlists and tracks grow', function () {
    playlistWith(MusicTrack::factory()->count(2)->create()->all());

    DB::enableQueryLog();
    getJson(route('study-room.music.playlists'))->assertOk();
    $baseline = count(DB::getQueryLog());

    foreach (range(1, 3) as $ignored) {
        playlistWith(MusicTrack::factory()->count(5)->create()->all());
    }

    DB::flushQueryLog();
    getJson(route('study-room.music.playlists'))->assertOk()->assertJsonCount(4, 'playlists');

    expect(count(DB::getQueryLog()))->toBe($baseline);
});
