<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Resources\Courses\Resources\PreviousExams\PreviousExamResource;
use App\Filament\Resources\Courses\Resources\PreviousExams\Schemas\PreviousExamForm;
use App\Filament\Resources\Courses\Resources\PreviousExams\Tables\PreviousExamsTable;
use App\Models\PreviousExam;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PreviousExamsRelationManager extends RelationManager
{
    protected static string $relationship = 'previousExams';

    protected static ?string $relatedResource = PreviousExamResource::class;

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return '考古題';
    }

    public function form(Schema $schema): Schema
    {
        return PreviousExamForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return PreviousExamsTable::configure($table)
            ->headerActions([
                Action::make('create')
                    ->label('新增考古題')
                    ->url(fn (): string => PreviousExamResource::getUrl('create', ['course' => $this->getOwnerRecord()])),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('編輯')
                    ->url(fn (PreviousExam $record): string => PreviousExamResource::getUrl('edit', [
                        'course' => $this->getOwnerRecord(),
                        'record' => $record,
                    ])),
                DeleteAction::make(),
            ]);
    }
}
