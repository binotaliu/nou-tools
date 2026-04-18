<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\Pages\CreateCourseClass;
use App\Filament\Resources\Courses\Resources\CourseClasses\Pages\EditCourseClass;
use App\Filament\Resources\Courses\Resources\CourseClasses\RelationManagers\SchedulesRelationManager;
use App\Filament\Resources\Courses\Resources\CourseClasses\Schemas\CourseClassForm;
use App\Filament\Resources\Courses\Resources\CourseClasses\Tables\CourseClassesTable;
use App\Models\CourseClass;
use BackedEnum;
use Filament\Resources\ParentResourceRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseClassResource extends Resource
{
    protected static ?string $model = CourseClass::class;

    protected static ?string $modelLabel = '班級';

    protected static ?string $pluralModelLabel = '班級';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = CourseResource::class;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return CourseClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseClassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'schedules' => SchedulesRelationManager::class,
        ];
    }

    public static function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return CourseResource::asParent()
            ->relationship('classes')
            ->inverseRelationship('course');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('schedules');
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateCourseClass::route('/create'),
            'edit' => EditCourseClass::route('/{record}/edit'),
        ];
    }
}
