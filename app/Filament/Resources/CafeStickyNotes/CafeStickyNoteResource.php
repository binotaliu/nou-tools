<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeStickyNotes;

use App\Filament\Resources\CafeStickyNotes\Pages\ListCafeStickyNotes;
use App\Filament\Resources\CafeStickyNotes\Tables\CafeStickyNotesTable;
use App\Models\CafeStickyNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CafeStickyNoteResource extends Resource
{
    protected static ?string $model = CafeStickyNote::class;

    protected static ?string $modelLabel = '便利貼';

    protected static ?string $pluralModelLabel = '便利貼';

    protected static string|UnitEnum|null $navigationGroup = '咖啡廳';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CafeStickyNotesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafeStickyNotes::route('/'),
        ];
    }
}
