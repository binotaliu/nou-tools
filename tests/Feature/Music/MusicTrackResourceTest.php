<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\MusicTracks\Pages\CreateMusicTrack;
use App\Filament\Resources\MusicTracks\Pages\EditMusicTrack;
use App\Filament\Resources\MusicTracks\Pages\ListMusicTracks;
use App\Models\MusicPlaylistItem;
use App\Models\MusicTrack;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function fakeAudio(string $extension): UploadedFile
{
    return UploadedFile::fake()->createWithContent("track.{$extension}", file_get_contents(base_path("tests/fixtures/audio/silence-2s.{$extension}")));
}

beforeEach(function () {
    Storage::fake(MusicTrack::AUDIO_DISK);

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('is admin only', function () {
    actingAs(User::factory()->create());

    get('/admin/music-tracks')->assertForbidden();
});

it('lists tracks', function () {
    $tracks = MusicTrack::factory()->count(3)->create();

    Livewire::test(ListMusicTracks::class)->assertCanSeeTableRecords($tracks);
});

it('creates a track from an mp3 and an ogg and reads the duration from the file', function () {
    Livewire::test(CreateMusicTrack::class)
        ->fillForm([
            'title' => 'Quiet Rain',
            'author' => 'Some Composer',
            'license' => 'CC BY 4.0',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'source_url' => 'https://example.com/quiet-rain',
            'mp3_path' => fakeAudio('mp3'),
            'ogg_path' => fakeAudio('ogg'),
        ])
        ->assertSet('data.duration_seconds', 2)
        ->call('create')
        ->assertHasNoFormErrors();

    $track = MusicTrack::query()->sole();

    expect($track->title)->toBe('Quiet Rain')
        ->and($track->author)->toBe('Some Composer')
        ->and($track->license)->toBe('CC BY 4.0')
        ->and($track->duration_seconds)->toBe(2);
    Storage::disk(MusicTrack::AUDIO_DISK)->assertExists([$track->mp3_path, $track->ogg_path]);
});

it('requires both audio formats and a duration when none can be read', function () {
    Livewire::test(CreateMusicTrack::class)
        ->fillForm(['title' => 'Quiet Rain', 'author' => 'Someone', 'license' => 'CC0', 'mp3_path' => fakeAudio('mp3')])
        ->call('create')
        ->assertHasFormErrors(['ogg_path' => 'required']);

    Livewire::test(CreateMusicTrack::class)
        ->fillForm(['title' => 'Quiet Rain', 'author' => 'Someone', 'license' => 'CC0', 'mp3_path' => UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg'), 'ogg_path' => UploadedFile::fake()->create('a.ogg', 10, 'audio/ogg')])
        ->call('create')
        ->assertHasFormErrors(['duration_seconds' => 'required']);

    expect(MusicTrack::count())->toBe(0);
});

it('rejects files that are not the expected audio type', function () {
    Livewire::test(CreateMusicTrack::class)
        ->fillForm([
            'title' => 'Quiet Rain',
            'author' => 'Someone',
            'license' => 'CC0',
            'duration_seconds' => 10,
            'mp3_path' => UploadedFile::fake()->create('a.mp3', 10, 'text/plain'),
            'ogg_path' => UploadedFile::fake()->image('b.png'),
        ])
        ->call('create')
        ->assertHasFormErrors(['mp3_path', 'ogg_path']);

    expect(MusicTrack::count())->toBe(0);
});

it('updates track details without touching the files', function () {
    $track = MusicTrack::factory()->create(['mp3_path' => 'a.mp3', 'ogg_path' => 'a.ogg']);
    Storage::disk(MusicTrack::AUDIO_DISK)->put('a.mp3', 'mp3');
    Storage::disk(MusicTrack::AUDIO_DISK)->put('a.ogg', 'ogg');

    Livewire::test(EditMusicTrack::class, ['record' => $track->getRouteKey()])
        ->fillForm(['title' => 'Renamed', 'license' => 'CC0'])
        ->call('save')
        ->assertHasNoFormErrors();

    $track->refresh();

    expect($track->title)->toBe('Renamed')
        ->and($track->license)->toBe('CC0')
        ->and($track->mp3_path)->toBe('a.mp3')
        ->and($track->ogg_path)->toBe('a.ogg');
    Storage::disk(MusicTrack::AUDIO_DISK)->assertExists(['a.mp3', 'a.ogg']);
});

it('deletes a track together with its files and playlist entries', function () {
    $track = MusicTrack::factory()->create(['mp3_path' => 'a.mp3', 'ogg_path' => 'a.ogg']);
    Storage::disk(MusicTrack::AUDIO_DISK)->put('a.mp3', 'mp3');
    Storage::disk(MusicTrack::AUDIO_DISK)->put('a.ogg', 'ogg');
    MusicPlaylistItem::factory()->for($track, 'track')->create();

    Livewire::test(EditMusicTrack::class, ['record' => $track->getRouteKey()])
        ->callAction(TestAction::make('delete'));

    expect(MusicTrack::count())->toBe(0)->and(MusicPlaylistItem::count())->toBe(0);
    Storage::disk(MusicTrack::AUDIO_DISK)->assertMissing(['a.mp3', 'a.ogg']);
});

it('falls back to the ogg duration when the mp3 is unreadable', function () {
    Livewire::test(CreateMusicTrack::class)
        ->fillForm([
            'mp3_path' => UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg'),
            'ogg_path' => fakeAudio('ogg'),
        ])
        ->assertSet('data.duration_seconds', 2);
});
