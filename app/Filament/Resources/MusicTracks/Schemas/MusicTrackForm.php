<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks\Schemas;

use App\Models\MusicTrack;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use NouTools\Domains\Music\Actions\ReadAudioDuration;

class MusicTrackForm
{
    /** Per-file upload cap in KB; keep in step with `temporary_file_upload.rules` in config/livewire.php. */
    private const int MAX_AUDIO_KB = 30720;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資訊')
                    ->schema([
                        TextInput::make('title')
                            ->label('曲名')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('author')
                            ->label('作者')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('授權')
                    ->schema([
                        TextInput::make('license')
                            ->label('授權')
                            ->placeholder('例：CC BY 4.0、CC0、Pixabay Content License')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('license_url')
                            ->label('授權連結')
                            ->url()
                            ->maxLength(2048),
                        TextInput::make('source_url')
                            ->label('來源連結')
                            ->helperText('原始曲目頁面，方便日後查證授權。')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('音檔')
                    ->description('mp3 與 ogg 需為同一首曲目，播放時瀏覽器會擇一使用。')
                    ->schema([
                        FileUpload::make('mp3_path')
                            ->label('MP3')
                            ->disk(MusicTrack::AUDIO_DISK)
                            ->acceptedFileTypes(['audio/mpeg'])
                            ->maxSize(self::MAX_AUDIO_KB)
                            ->required()
                            ->afterStateUpdated(fn (mixed $state, Get $get, Set $set) => self::fillDuration($state, $get, $set, overwrite: true)),
                        FileUpload::make('ogg_path')
                            ->label('OGG')
                            ->disk(MusicTrack::AUDIO_DISK)
                            ->acceptedFileTypes(['audio/ogg', 'application/ogg', 'audio/x-vorbis+ogg'])
                            ->maxSize(self::MAX_AUDIO_KB)
                            ->required()
                            ->afterStateUpdated(fn (mixed $state, Get $get, Set $set) => self::fillDuration($state, $get, $set, overwrite: false)),
                        TextInput::make('duration_seconds')
                            ->label('長度')
                            ->helperText('上傳音檔後自動帶入，可手動修正。')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->suffix('秒')
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Fill `duration_seconds` from a freshly uploaded file. The mp3 always
     * wins; the ogg only fills a blank so it can't overwrite the mp3's value.
     */
    private static function fillDuration(mixed $state, Get $get, Set $set, bool $overwrite): void
    {
        $file = Arr::first(Arr::wrap($state), fn (mixed $value): bool => $value instanceof TemporaryUploadedFile);

        if ($file === null || (! $overwrite && filled($get('duration_seconds')))) {
            return;
        }

        $seconds = app(ReadAudioDuration::class)($file->getRealPath());

        if ($seconds !== null) {
            $set('duration_seconds', $seconds);
        }
    }
}
