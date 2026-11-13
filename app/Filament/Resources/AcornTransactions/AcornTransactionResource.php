<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcornTransactions;

use App\Filament\Resources\AcornTransactions\Pages\ListAcornTransactions;
use App\Filament\Resources\AcornTransactions\Tables\AcornTransactionsTable;
use App\Models\AcornTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The acorn ledger: every credit and spend, plus a way for admins to give
 * acorns to a schedule.
 */
final class AcornTransactionResource extends Resource
{
    protected static ?string $model = AcornTransaction::class;

    protected static ?string $modelLabel = '橡實紀錄';

    protected static ?string $pluralModelLabel = '橡實紀錄';

    protected static string|UnitEnum|null $navigationGroup = '咖啡廳';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return AcornTransactionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('schedule.studyRoomProfile');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAcornTransactions::route('/'),
        ];
    }
}
