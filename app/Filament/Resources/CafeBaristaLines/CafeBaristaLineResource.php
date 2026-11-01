<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeBaristaLines;

use App\Filament\Resources\CafeBaristaLines\Pages\CreateCafeBaristaLine;
use App\Filament\Resources\CafeBaristaLines\Pages\EditCafeBaristaLine;
use App\Filament\Resources\CafeBaristaLines\Pages\ListCafeBaristaLines;
use App\Filament\Resources\CafeBaristaLines\Schemas\CafeBaristaLineForm;
use App\Filament\Resources\CafeBaristaLines\Tables\CafeBaristaLinesTable;
use App\Models\CafeBaristaLine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CafeBaristaLineResource extends Resource
{
    protected static ?string $model = CafeBaristaLine::class;

    protected static ?string $modelLabel = '店長台詞';

    protected static ?string $pluralModelLabel = '店長台詞';

    protected static string|UnitEnum|null $navigationGroup = '咖啡廳';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'text';

    public static function form(Schema $schema): Schema
    {
        return CafeBaristaLineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CafeBaristaLinesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafeBaristaLines::route('/'),
            'create' => CreateCafeBaristaLine::route('/create'),
            'edit' => EditCafeBaristaLine::route('/{record}/edit'),
        ];
    }
}
