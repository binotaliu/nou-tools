<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Courses\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\Schemas\CourseClassForm;
use App\Filament\Resources\Courses\Resources\CourseClasses\Tables\CourseClassesTable;
use App\Models\CourseClass;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ClassesRelationManager extends RelationManager
{
    protected static string $relationship = 'classes';

    protected static ?string $relatedResource = CourseClassResource::class;

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return '班級';
    }

    public function form(Schema $schema): Schema
    {
        return CourseClassForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return CourseClassesTable::configure($table)
            ->headerActions([
                Action::make('create')
                    ->label('新增班級')
                    ->url(fn (): string => CourseClassResource::getUrl('create', ['course' => $this->getOwnerRecord()])),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('編輯')
                    ->url(fn (CourseClass $record): string => CourseClassResource::getUrl('edit', [
                        'course' => $this->getOwnerRecord(),
                        'record' => $record,
                    ])),
                DeleteAction::make(),
            ]);
    }
}
