<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiPages;

use App\Filament\Resources\WikiPages\Pages\CreateWikiPage;
use App\Filament\Resources\WikiPages\Pages\EditWikiPage;
use App\Filament\Resources\WikiPages\Pages\ListWikiPages;
use App\Filament\Resources\WikiPages\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\WikiPages\Schemas\WikiPageForm;
use App\Filament\Resources\WikiPages\Tables\WikiPagesTable;
use App\Models\WikiPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class WikiPageResource extends Resource
{
    protected static ?string $model = WikiPage::class;

    protected static ?string $modelLabel = '條目';

    protected static ?string $pluralModelLabel = '條目';

    protected static string|UnitEnum|null $navigationGroup = '知識庫';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function form(Schema $schema): Schema
    {
        return WikiPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WikiPagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RevisionsRelationManager::class,
        ];
    }

    /**
     * @return Builder<WikiPage>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('currentRevision')
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWikiPages::route('/'),
            'create' => CreateWikiPage::route('/create'),
            'edit' => EditWikiPage::route('/{record}/edit'),
        ];
    }
}
