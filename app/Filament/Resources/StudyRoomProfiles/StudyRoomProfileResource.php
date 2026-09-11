<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyRoomProfiles;

use App\Filament\Resources\StudyRoomProfiles\Pages\ListStudyRoomProfiles;
use App\Filament\Resources\StudyRoomProfiles\Tables\StudyRoomProfilesTable;
use App\Models\StudyRoomProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class StudyRoomProfileResource extends Resource
{
    protected static ?string $model = StudyRoomProfile::class;

    protected static ?string $modelLabel = '自習室暱稱';

    protected static ?string $pluralModelLabel = '自習室暱稱';

    protected static string|UnitEnum|null $navigationGroup = '自習室';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return StudyRoomProfilesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['schedule.studyRoomSeat']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudyRoomProfiles::route('/'),
        ];
    }
}
