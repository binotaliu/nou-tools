<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicPlaylists;

use App\Filament\Resources\MusicPlaylists\Pages\CreateMusicPlaylist;
use App\Filament\Resources\MusicPlaylists\Pages\EditMusicPlaylist;
use App\Filament\Resources\MusicPlaylists\Pages\ListMusicPlaylists;
use App\Filament\Resources\MusicPlaylists\Schemas\MusicPlaylistForm;
use App\Filament\Resources\MusicPlaylists\Tables\MusicPlaylistsTable;
use App\Models\MusicPlaylist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MusicPlaylistResource extends Resource
{
    protected static ?string $model = MusicPlaylist::class;

    protected static ?string $modelLabel = '播放清單';

    protected static ?string $pluralModelLabel = '播放清單';

    protected static string|UnitEnum|null $navigationGroup = '自習室';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return MusicPlaylistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MusicPlaylistsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMusicPlaylists::route('/'),
            'create' => CreateMusicPlaylist::route('/create'),
            'edit' => EditMusicPlaylist::route('/{record}/edit'),
        ];
    }
}
