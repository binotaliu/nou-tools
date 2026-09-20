<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicPlaylists\Schemas;

use App\Models\MusicPlaylist;
use App\Models\MusicTrack;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Component;

class MusicPlaylistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資訊')
                    ->schema([
                        TextInput::make('title')
                            ->label('標題')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('說明')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        FileUpload::make('cover_image')
                            ->label('封面圖片')
                            ->helperText('會自動裁成正方形。')
                            ->image()
                            ->disk(MusicPlaylist::COVER_DISK)
                            ->imageAspectRatio('1:1')
                            ->automaticallyCropImagesToAspectRatio()
                            ->automaticallyResizeImagesToWidth('800')
                            ->automaticallyResizeImagesToHeight('800')
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('曲目')
                    ->key('tracks')
                    ->description('依序播放，可拖曳調整順序。同一首曲目在一個清單中只能出現一次。')
                    ->headerActions([self::addMultipleTracksAction()])
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->schema([
                                Select::make('music_track_id')
                                    ->label('曲目')
                                    ->relationship('track', 'title')
                                    ->getOptionLabelFromRecordUsing(fn (MusicTrack $track): string => "{$track->title}｜{$track->author}")
                                    ->searchable(['title', 'author'])
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->itemLabel(fn (array $state): ?string => filled($state['music_track_id'] ?? null)
                                ? MusicTrack::query()->find($state['music_track_id'])?->title
                                : null)
                            ->defaultItems(0)
                            ->addActionLabel('加入曲目'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function addMultipleTracksAction(): Action
    {
        return Action::make('addMultipleTracks')
            ->label('批次加入曲目')
            ->icon('heroicon-o-queue-list')
            ->color('gray')
            ->modalHeading('批次加入曲目')
            ->modalSubmitActionLabel('加入')
            ->schema([
                Select::make('track_ids')
                    ->label('曲目')
                    ->helperText('已在清單中的曲目不會顯示。選取的曲目會依選取順序接在清單最後。')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->options(fn (Component $livewire): array => self::availableTrackOptions(self::selectedTrackIds($livewire->data['items'] ?? []))),
            ])
            ->action(function (array $data, Get $get, Set $set): void {
                $items = $get('items') ?? [];

                foreach ($data['track_ids'] as $trackId) {
                    $items[(string) Str::uuid()] = ['music_track_id' => $trackId];
                }

                $set('items', $items);
            });
    }

    /**
     * @param  array<array-key, array{music_track_id?: int|string|null}>|null  $items
     * @return list<int|string>
     */
    private static function selectedTrackIds(?array $items): array
    {
        return collect($items ?? [])->pluck('music_track_id')->filter()->values()->all();
    }

    /**
     * @param  list<int|string>  $excludedIds
     * @return array<int, string>
     */
    private static function availableTrackOptions(array $excludedIds): array
    {
        return MusicTrack::query()
            ->whereNotIn('id', $excludedIds)
            ->orderBy('title')
            ->get()
            ->mapWithKeys(fn (MusicTrack $track): array => [$track->id => "{$track->title}｜{$track->author}"])
            ->all();
    }
}
