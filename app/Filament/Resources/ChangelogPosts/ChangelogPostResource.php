<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangelogPosts;

use App\Filament\Resources\ChangelogPosts\Pages\CreateChangelogPost;
use App\Filament\Resources\ChangelogPosts\Pages\EditChangelogPost;
use App\Filament\Resources\ChangelogPosts\Pages\ListChangelogPosts;
use App\Filament\Resources\ChangelogPosts\Schemas\ChangelogPostForm;
use App\Filament\Resources\ChangelogPosts\Tables\ChangelogPostsTable;
use App\Models\ChangelogPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ChangelogPostResource extends Resource
{
    protected static ?string $model = ChangelogPost::class;

    protected static ?string $modelLabel = '更新日誌';

    protected static ?string $pluralModelLabel = '更新日誌';

    protected static string|UnitEnum|null $navigationGroup = '最新資訊';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ChangelogPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChangelogPostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangelogPosts::route('/'),
            'create' => CreateChangelogPost::route('/create'),
            'edit' => EditChangelogPost::route('/{record}/edit'),
        ];
    }
}
