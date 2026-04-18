<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\RelationManagers;

use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\ClassScheduleResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Schemas\ClassScheduleForm;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Tables\ClassSchedulesTable;
use App\Models\ClassSchedule;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $relatedResource = ClassScheduleResource::class;

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return '視訊面授日期';
    }

    public function form(Schema $schema): Schema
    {
        return ClassScheduleForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return ClassSchedulesTable::configure($table)
            ->headerActions([
                Action::make('create')
                    ->label('新增面授日期')
                    ->url(fn (): string => ClassScheduleResource::getUrl('create', [
                        'course' => $this->getOwnerRecord()->course,
                        'course_class' => $this->getOwnerRecord(),
                    ])),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('編輯')
                    ->url(fn (ClassSchedule $record): string => ClassScheduleResource::getUrl('edit', [
                        'course' => $this->getOwnerRecord()->course,
                        'course_class' => $this->getOwnerRecord(),
                        'record' => $record,
                    ])),
                DeleteAction::make(),
            ]);
    }
}
