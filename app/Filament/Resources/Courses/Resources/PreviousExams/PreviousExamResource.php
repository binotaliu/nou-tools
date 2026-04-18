<?php

namespace App\Filament\Resources\Courses\Resources\PreviousExams;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Resources\PreviousExams\Pages\CreatePreviousExam;
use App\Filament\Resources\Courses\Resources\PreviousExams\Pages\EditPreviousExam;
use App\Filament\Resources\Courses\Resources\PreviousExams\Schemas\PreviousExamForm;
use App\Filament\Resources\Courses\Resources\PreviousExams\Tables\PreviousExamsTable;
use App\Models\PreviousExam;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PreviousExamResource extends Resource
{
    protected static ?string $model = PreviousExam::class;

    protected static ?string $modelLabel = '考古題';

    protected static ?string $pluralModelLabel = '考古題';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = CourseResource::class;

    protected static ?string $recordTitleAttribute = 'term';

    public static function form(Schema $schema): Schema
    {
        return PreviousExamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PreviousExamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreatePreviousExam::route('/create'),
            'edit' => EditPreviousExam::route('/{record}/edit'),
        ];
    }
}
