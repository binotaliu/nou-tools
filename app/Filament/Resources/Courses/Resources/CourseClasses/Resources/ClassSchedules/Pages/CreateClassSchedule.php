<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\ClassScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassSchedule extends CreateRecord
{
    protected static string $resource = ClassScheduleResource::class;

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
            '新增面授日期',
        ];
    }
}
