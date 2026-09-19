<?php

declare(strict_types=1);

namespace NouTools\Domains\Music\ViewModels;

use App\Models\MusicTrack;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Data;

final class MusicTrackViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $author,
        public string $license,
        public ?string $licenseUrl,
        public ?string $sourceUrl,
        public int $durationSeconds,
        public string $audioMp3Url,
        public string $audioOggUrl,
    ) {}

    public static function fromModel(MusicTrack $track): self
    {
        $disk = Storage::disk(MusicTrack::AUDIO_DISK);

        return new self(
            id: $track->id,
            title: $track->title,
            author: $track->author,
            license: $track->license,
            licenseUrl: $track->license_url,
            sourceUrl: $track->source_url,
            durationSeconds: $track->duration_seconds,
            audioMp3Url: $disk->url($track->mp3_path),
            audioOggUrl: $disk->url($track->ogg_path),
        );
    }
}
