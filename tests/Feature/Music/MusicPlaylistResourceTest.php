<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\MusicPlaylists\Pages\CreateMusicPlaylist;
use App\Filament\Resources\MusicPlaylists\Pages\EditMusicPlaylist;
use App\Filament\Resources\MusicPlaylists\Pages\ListMusicPlaylists;
use App\Models\MusicPlaylist;
use App\Models\MusicPlaylistItem;
use App\Models\MusicTrack;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Storage::fake(MusicPlaylist::COVER_DISK);

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('is admin only', function () {
    actingAs(User::factory()->create());

    get('/admin/music-playlists')->assertForbidden();
});

it('lists playlists', function () {
    $playlists = MusicPlaylist::factory()->count(3)->create();

    Livewire::test(ListMusicPlaylists::class)->assertCanSeeTableRecords($playlists);
});

it('shows the track count and total length of a playlist', function () {
    $playlist = MusicPlaylist::factory()->create();
    foreach ([60, 125] as $seconds) {
        MusicPlaylistItem::factory()->for($playlist, 'playlist')->for(MusicTrack::factory()->state(['duration_seconds' => $seconds]), 'track')->create();
    }

    Livewire::test(ListMusicPlaylists::class)
        ->assertTableColumnStateSet('items_count', 2, $playlist)
        ->assertTableColumnFormattedStateSet('tracks_sum_duration_seconds', '03:05', $playlist);
});

it('creates a playlist with a cover image and ordered tracks', function () {
    [$first, $second, $third] = MusicTrack::factory()->count(3)->create()->all();

    Livewire::test(CreateMusicPlaylist::class)
        ->fillForm([
            'title' => 'Rainy Focus',
            'description' => 'Soft rain and piano.',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'items' => [
                ['music_track_id' => $third->id],
                ['music_track_id' => $first->id],
                ['music_track_id' => $second->id],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $playlist = MusicPlaylist::query()->sole();

    expect($playlist->title)->toBe('Rainy Focus')
        ->and($playlist->description)->toBe('Soft rain and piano.')
        ->and($playlist->tracks->pluck('id')->all())->toBe([$third->id, $first->id, $second->id]);
    Storage::disk(MusicPlaylist::COVER_DISK)->assertExists($playlist->cover_image);
});

it('requires a title', function () {
    Livewire::test(CreateMusicPlaylist::class)
        ->fillForm(['title' => null])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required']);
});

it('rejects the same track twice in one playlist', function () {
    $track = MusicTrack::factory()->create();

    Livewire::test(CreateMusicPlaylist::class)
        ->fillForm([
            'title' => 'Dupes',
            'items' => [
                ['music_track_id' => $track->id],
                ['music_track_id' => $track->id],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(MusicPlaylist::count())->toBe(0);
});

it('reorders and removes tracks when editing', function () {
    $playlist = MusicPlaylist::factory()->create();
    [$first, $second, $third] = MusicTrack::factory()->count(3)->create()->all();
    foreach ([$first, $second, $third] as $position => $track) {
        MusicPlaylistItem::factory()->for($playlist, 'playlist')->for($track, 'track')->create(['position' => $position]);
    }

    $items = $playlist->items->pluck('id')->all();

    Livewire::test(EditMusicPlaylist::class, ['record' => $playlist->getRouteKey()])
        ->set('data.items', [
            "record-{$items[2]}" => ['music_track_id' => $third->id],
            "record-{$items[0]}" => ['music_track_id' => $first->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($playlist->refresh()->tracks->pluck('id')->all())->toBe([$third->id, $first->id]);
    expect(MusicTrack::count())->toBe(3);
});

it('deletes a playlist but keeps its tracks', function () {
    $playlist = MusicPlaylist::factory()->create();
    MusicPlaylistItem::factory()->for($playlist, 'playlist')->create();

    Livewire::test(EditMusicPlaylist::class, ['record' => $playlist->getRouteKey()])
        ->callAction(TestAction::make('delete'));

    expect(MusicPlaylist::count())->toBe(0)->and(MusicTrack::count())->toBe(1);
});
