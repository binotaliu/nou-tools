<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStoreCategories;

use App\Filament\Resources\DiscountStoreCategories\Pages\CreateDiscountStoreCategory;
use App\Filament\Resources\DiscountStoreCategories\Pages\EditDiscountStoreCategory;
use App\Filament\Resources\DiscountStoreCategories\Pages\ListDiscountStoreCategories;
use App\Filament\Resources\DiscountStoreCategories\Schemas\DiscountStoreCategoryForm;
use App\Filament\Resources\DiscountStoreCategories\Tables\DiscountStoreCategoriesTable;
use App\Models\DiscountStoreCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DiscountStoreCategoryResource extends Resource
{
    protected static ?string $model = DiscountStoreCategory::class;

    protected static ?string $modelLabel = '優惠店家分類';

    protected static ?string $pluralModelLabel = '優惠店家分類';

    protected static string|UnitEnum|null $navigationGroup = '優惠店家';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return DiscountStoreCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountStoreCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscountStoreCategories::route('/'),
            'create' => CreateDiscountStoreCategory::route('/create'),
            'edit' => EditDiscountStoreCategory::route('/{record}/edit'),
        ];
    }
}
