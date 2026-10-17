<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditEvents;

use App\Filament\Resources\WikiEditEvents\Pages\ListWikiEditEvents;
use App\Filament\Resources\WikiEditEvents\Tables\WikiEditEventsTable;
use App\Models\WikiEditEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The audit trail of the edit flow: who asked for what and how each request
 * ended, including the ones that failed or were turned down.
 */
final class WikiEditEventResource extends Resource
{
    protected static ?string $model = WikiEditEvent::class;

    protected static ?string $modelLabel = '修改紀錄';

    protected static ?string $pluralModelLabel = '修改紀錄';

    protected static string|UnitEnum|null $navigationGroup = '知識庫';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function table(Table $table): Table
    {
        return WikiEditEventsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['studentSchedule', 'user']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWikiEditEvents::route('/'),
        ];
    }
}
