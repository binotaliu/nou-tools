<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks;

use App\Filament\Resources\MusicTracks\Pages\CreateMusicTrack;
use App\Filament\Resources\MusicTracks\Pages\EditMusicTrack;
use App\Filament\Resources\MusicTracks\Pages\ListMusicTracks;
use App\Filament\Resources\MusicTracks\Schemas\MusicTrackForm;
use App\Filament\Resources\MusicTracks\Tables\MusicTracksTable;
use App\Models\MusicTrack;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MusicTrackResource extends Resource
{
    protected static ?string $model = MusicTrack::class;

    protected static ?string $modelLabel = '音樂曲目';

    protected static ?string $pluralModelLabel = '音樂曲目';

    protected static string|UnitEnum|null $navigationGroup = '自習室';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return MusicTrackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MusicTracksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMusicTracks::route('/'),
            'create' => CreateMusicTrack::route('/create'),
            'edit' => EditMusicTrack::route('/{record}/edit'),
        ];
    }
}
