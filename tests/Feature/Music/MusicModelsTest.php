<?php

declare(strict_types=1);

use App\Models\MusicPlaylist;
use App\Models\MusicPlaylistItem;
use App\Models\MusicTrack;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

it('lists a playlist\'s tracks in position order', function (): void {
    $playlist = MusicPlaylist::factory()->create();
    [$first, $second, $third] = MusicTrack::factory()->count(3)->create()->all();

    MusicPlaylistItem::factory()->for($playlist, 'playlist')->for($third, 'track')->create(['position' => 0]);
    MusicPlaylistItem::factory()->for($playlist, 'playlist')->for($first, 'track')->create(['position' => 1]);
    MusicPlaylistItem::factory()->for($playlist, 'playlist')->for($second, 'track')->create(['position' => 2]);

    expect($playlist->tracks->pluck('id')->all())->toBe([$third->id, $first->id, $second->id])
        ->and($playlist->items->pluck('music_track_id')->all())->toBe([$third->id, $first->id, $second->id]);
});

it('does not allow the same track twice in one playlist', function (): void {
    $playlist = MusicPlaylist::factory()->create();
    $track = MusicTrack::factory()->create();

    MusicPlaylistItem::factory()->for($playlist, 'playlist')->for($track, 'track')->create();

    MusicPlaylistItem::factory()->for($playlist, 'playlist')->for($track, 'track')->create();
})->throws(QueryException::class);

it('removes a deleted track\'s files and playlist entries', function (): void {
    Storage::fake(MusicTrack::AUDIO_DISK);

    $track = MusicTrack::factory()->create(['mp3_path' => 'a.mp3', 'ogg_path' => 'a.ogg']);
    Storage::disk(MusicTrack::AUDIO_DISK)->put('a.mp3', 'mp3');
    Storage::disk(MusicTrack::AUDIO_DISK)->put('a.ogg', 'ogg');
    MusicPlaylistItem::factory()->for($track, 'track')->create();

    $track->delete();

    Storage::disk(MusicTrack::AUDIO_DISK)->assertMissing('a.mp3');
    Storage::disk(MusicTrack::AUDIO_DISK)->assertMissing('a.ogg');
    expect(MusicPlaylistItem::count())->toBe(0);
});

it('removes a deleted playlist\'s cover image but keeps its tracks', function (): void {
    Storage::fake(MusicPlaylist::COVER_DISK);

    $playlist = MusicPlaylist::factory()->create(['cover_image' => 'cover.jpg']);
    Storage::disk(MusicPlaylist::COVER_DISK)->put('cover.jpg', 'img');
    $item = MusicPlaylistItem::factory()->for($playlist, 'playlist')->create();

    $playlist->delete();

    Storage::disk(MusicPlaylist::COVER_DISK)->assertMissing('cover.jpg');
    expect(MusicPlaylistItem::count())->toBe(0)
        ->and(MusicTrack::find($item->music_track_id))->not->toBeNull();
});
