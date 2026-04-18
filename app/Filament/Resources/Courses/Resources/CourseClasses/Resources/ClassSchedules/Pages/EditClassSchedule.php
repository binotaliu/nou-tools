<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\ClassScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassSchedule extends EditRecord
{
    protected static string $resource = ClassScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $courseClass = $this->getParentRecord();
        $course = $courseClass?->course;

        if ($course === null || $courseClass === null) {
            return parent::getBreadcrumbs();
        }

        return [
            CourseResource::getUrl() => '課程',
            CourseResource::getUrl('edit', ['record' => $course]) => $course->name,
            CourseClassResource::getUrl('edit', [
                'course' => $course,
                'record' => $courseClass,
            ]) => $courseClass->code,
            $this->getRecordTitle(),
        ];
    }
}
