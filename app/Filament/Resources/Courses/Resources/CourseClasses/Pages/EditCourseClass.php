<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Pages;

use App\Filament\Resources\Courses\Resources\CourseClasses\CourseClassResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCourseClass extends EditRecord
{
    protected static string $resource = CourseClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
