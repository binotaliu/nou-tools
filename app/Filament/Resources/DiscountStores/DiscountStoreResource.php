<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStores;

use App\Filament\Resources\DiscountStores\Pages\CreateDiscountStore;
use App\Filament\Resources\DiscountStores\Pages\EditDiscountStore;
use App\Filament\Resources\DiscountStores\Pages\ListDiscountStores;
use App\Filament\Resources\DiscountStores\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\DiscountStores\RelationManagers\ReportsRelationManager;
use App\Filament\Resources\DiscountStores\Schemas\DiscountStoreForm;
use App\Filament\Resources\DiscountStores\Tables\DiscountStoresTable;
use App\Models\DiscountStore;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DiscountStoreResource extends Resource
{
    protected static ?string $model = DiscountStore::class;

    protected static ?string $modelLabel = '優惠店家';

    protected static ?string $pluralModelLabel = '優惠店家';

    protected static string|UnitEnum|null $navigationGroup = '優惠店家';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DiscountStoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountStoresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'reports' => ReportsRelationManager::class,
            'comments' => CommentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category'])
            ->withCount(['reports', 'comments']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscountStores::route('/'),
            'create' => CreateDiscountStore::route('/create'),
            'edit' => EditDiscountStore::route('/{record}/edit'),
        ];
    }
}
