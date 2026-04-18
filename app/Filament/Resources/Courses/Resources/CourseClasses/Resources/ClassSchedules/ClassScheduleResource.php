<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules;

use App\Filament\Resources\Courses\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Pages\CreateClassSchedule;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Pages\EditClassSchedule;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Schemas\ClassScheduleForm;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Tables\ClassSchedulesTable;
use App\Models\ClassSchedule;
use BackedEnum;
use Filament\Resources\ParentResourceRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClassScheduleResource extends Resource
{
    protected static ?string $model = ClassSchedule::class;

    protected static ?string $modelLabel = '面授日期';

    protected static ?string $pluralModelLabel = '面授日期';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = CourseClassResource::class;

    protected static ?string $recordTitleAttribute = 'date';

    public static function form(Schema $schema): Schema
    {
        return ClassScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassSchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return CourseClassResource::asParent()
            ->relationship('schedules')
            ->inverseRelationship('courseClass');
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateClassSchedule::route('/create'),
            'edit' => EditClassSchedule::route('/{record}/edit'),
        ];
    }
}
