<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditRequests;

use App\Enums\WikiEditRequestStatus;
use App\Filament\Resources\WikiEditRequests\Pages\ListWikiEditRequests;
use App\Filament\Resources\WikiEditRequests\Pages\ViewWikiEditRequest;
use App\Filament\Resources\WikiEditRequests\Schemas\WikiEditRequestInfolist;
use App\Filament\Resources\WikiEditRequests\Tables\WikiEditRequestsTable;
use App\Models\WikiEditRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The review queue for AI-drafted edits that didn't publish on their own,
 * and the log of every other request.
 */
class WikiEditRequestResource extends Resource
{
    protected static ?string $model = WikiEditRequest::class;

    protected static ?string $modelLabel = '修改請求';

    protected static ?string $pluralModelLabel = '修改請求';

    protected static string|UnitEnum|null $navigationGroup = '知識庫';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    public static function getNavigationBadge(): ?string
    {
        $pending = WikiEditRequest::query()->where('status', WikiEditRequestStatus::PendingReview)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return WikiEditRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WikiEditRequestsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWikiEditRequests::route('/'),
            'view' => ViewWikiEditRequest::route('/{record}'),
        ];
    }
}
