<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterIssues;

use App\Filament\Resources\NewsletterIssues\Pages\CreateNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\EditNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\ListNewsletterIssues;
use App\Filament\Resources\NewsletterIssues\Schemas\NewsletterIssueForm;
use App\Filament\Resources\NewsletterIssues\Tables\NewsletterIssuesTable;
use App\Models\NewsletterIssue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterIssueResource extends Resource
{
    protected static ?string $model = NewsletterIssue::class;

    protected static ?string $modelLabel = '雙週報';

    protected static ?string $pluralModelLabel = '雙週報';

    protected static string|UnitEnum|null $navigationGroup = '最新資訊';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'issue_key';

    public static function form(Schema $schema): Schema
    {
        return NewsletterIssueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsletterIssuesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterIssues::route('/'),
            'create' => CreateNewsletterIssue::route('/create'),
            'edit' => EditNewsletterIssue::route('/{record}/edit'),
        ];
    }
}
