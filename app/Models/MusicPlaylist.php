<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MusicPlaylistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

final class MusicPlaylist extends Model
{
    /** @use HasFactory<MusicPlaylistFactory> */
    use HasFactory;

    /** Scoped filesystem disk (see config/filesystems.php) holding `cover_image` files, relative to its own directory. */
    public const string COVER_DISK = 'music_playlist_covers';

    protected $fillable = [
        'title',
        'description',
        'cover_image',
    ];

    protected static function booted(): void
    {
        self::updated(function (MusicPlaylist $playlist): void {
            $previousCover = $playlist->getPrevious()['cover_image'] ?? null;

            if ($playlist->wasChanged('cover_image') && $previousCover !== null) {
                Storage::disk(self::COVER_DISK)->delete($previousCover);
            }
        });

        self::deleted(function (MusicPlaylist $playlist): void {
            if ($playlist->cover_image !== null) {
                Storage::disk(self::COVER_DISK)->delete($playlist->cover_image);
            }
        });
    }

    /**
     * @return HasMany<MusicPlaylistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MusicPlaylistItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<MusicTrack, $this>
     */
    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(MusicTrack::class, 'music_playlist_items')
            ->withPivot('position')
            ->orderByPivot('position')
            ->orderByPivot('id');
    }
}
