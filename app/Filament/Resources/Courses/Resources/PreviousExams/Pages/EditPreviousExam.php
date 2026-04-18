<?php

namespace App\Filament\Resources\Courses\Resources\PreviousExams\Pages;

use App\Filament\Resources\Courses\Resources\PreviousExams\PreviousExamResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPreviousExam extends EditRecord
{
    protected static string $resource = PreviousExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
