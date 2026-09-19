<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MusicTrackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

final class MusicTrack extends Model
{
    /** @use HasFactory<MusicTrackFactory> */
    use HasFactory;

    /** Scoped filesystem disk (see config/filesystems.php) holding `mp3_path` / `ogg_path` files, relative to its own directory. */
    public const string AUDIO_DISK = 'music_tracks';

    protected $fillable = [
        'title',
        'author',
        'license',
        'license_url',
        'source_url',
        'mp3_path',
        'ogg_path',
        'duration_seconds',
    ];

    protected static function booted(): void
    {
        self::deleted(function (MusicTrack $track): void {
            Storage::disk(self::AUDIO_DISK)->delete([$track->mp3_path, $track->ogg_path]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * @return HasMany<MusicPlaylistItem, $this>
     */
    public function playlistItems(): HasMany
    {
        return $this->hasMany(MusicPlaylistItem::class);
    }

    /**
     * @return BelongsToMany<MusicPlaylist, $this>
     */
    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(MusicPlaylist::class, 'music_playlist_items');
    }
}
