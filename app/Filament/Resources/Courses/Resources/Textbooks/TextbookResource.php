<?php

namespace App\Filament\Resources\Courses\Resources\Textbooks;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Resources\Textbooks\Pages\CreateTextbook;
use App\Filament\Resources\Courses\Resources\Textbooks\Pages\EditTextbook;
use App\Filament\Resources\Courses\Resources\Textbooks\Schemas\TextbookForm;
use App\Filament\Resources\Courses\Resources\Textbooks\Tables\TextbooksTable;
use App\Models\Textbook;
use BackedEnum;
use Filament\Resources\ParentResourceRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TextbookResource extends Resource
{
    protected static ?string $model = Textbook::class;

    protected static ?string $modelLabel = '教科書';

    protected static ?string $pluralModelLabel = '教科書';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = CourseResource::class;

    protected static ?string $recordTitleAttribute = 'book_title';

    public static function form(Schema $schema): Schema
    {
        return TextbookForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TextbooksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return CourseResource::asParent()
            ->relationship('textbook')
            ->inverseRelationship('course');
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateTextbook::route('/create'),
            'edit' => EditTextbook::route('/{record}/edit'),
        ];
    }
}
