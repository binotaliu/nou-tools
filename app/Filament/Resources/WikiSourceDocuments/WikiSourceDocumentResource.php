<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiSourceDocuments;

use App\Filament\Resources\WikiSourceDocuments\Pages\CreateWikiSourceDocument;
use App\Filament\Resources\WikiSourceDocuments\Pages\EditWikiSourceDocument;
use App\Filament\Resources\WikiSourceDocuments\Pages\ListWikiSourceDocuments;
use App\Filament\Resources\WikiSourceDocuments\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\WikiSourceDocuments\Schemas\WikiSourceDocumentForm;
use App\Filament\Resources\WikiSourceDocuments\Tables\WikiSourceDocumentsTable;
use App\Models\WikiSourceDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WikiSourceDocumentResource extends Resource
{
    protected static ?string $model = WikiSourceDocument::class;

    protected static ?string $modelLabel = '來源文件';

    protected static ?string $pluralModelLabel = '來源文件';

    protected static string|UnitEnum|null $navigationGroup = '知識庫';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return WikiSourceDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WikiSourceDocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWikiSourceDocuments::route('/'),
            'create' => CreateWikiSourceDocument::route('/create'),
            'edit' => EditWikiSourceDocument::route('/{record}/edit'),
        ];
    }
}
