<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicPlaylists\Schemas;

use App\Models\MusicPlaylist;
use App\Models\MusicTrack;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                    ->description('依序播放，可拖曳調整順序。同一首曲目在一個清單中只能出現一次。')
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
}
