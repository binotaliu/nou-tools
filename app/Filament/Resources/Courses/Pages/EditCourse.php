<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Resources\Textbooks\TextbookResource;
use App\Models\PreviousExam;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected ?string $previousCourseName = null;

    protected function getHeaderActions(): array
    {
        $this->record->loadMissing('textbook');

        return [
            Action::make('manageTextbook')
                ->label($this->record->textbook === null ? '新增教科書' : '編輯教科書')
                ->url(
                    $this->record->textbook === null
                        ? TextbookResource::getUrl('create', ['course' => $this->record])
                        : TextbookResource::getUrl('edit', [
                            'course' => $this->record,
                            'record' => $this->record->textbook,
                        ])
                ),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousCourseName = $this->record->name;

        return $data;
    }

    protected function afterSave(): void
    {
        if (blank($this->previousCourseName) || $this->previousCourseName === $this->record->name) {
            return;
        }

        PreviousExam::query()
            ->where('course_name', $this->previousCourseName)
            ->update(['course_name' => $this->record->name]);
    }
}
