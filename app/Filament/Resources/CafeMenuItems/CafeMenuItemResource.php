<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeMenuItems;

use App\Filament\Resources\CafeMenuItems\Pages\CreateCafeMenuItem;
use App\Filament\Resources\CafeMenuItems\Pages\EditCafeMenuItem;
use App\Filament\Resources\CafeMenuItems\Pages\ListCafeMenuItems;
use App\Filament\Resources\CafeMenuItems\Schemas\CafeMenuItemForm;
use App\Filament\Resources\CafeMenuItems\Tables\CafeMenuItemsTable;
use App\Models\CafeMenuItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CafeMenuItemResource extends Resource
{
    protected static ?string $model = CafeMenuItem::class;

    protected static ?string $modelLabel = '飲品';

    protected static ?string $pluralModelLabel = '菜單';

    protected static string|UnitEnum|null $navigationGroup = '咖啡廳';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CafeMenuItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CafeMenuItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafeMenuItems::route('/'),
            'create' => CreateCafeMenuItem::route('/create'),
            'edit' => EditCafeMenuItem::route('/{record}/edit'),
        ];
    }
}
