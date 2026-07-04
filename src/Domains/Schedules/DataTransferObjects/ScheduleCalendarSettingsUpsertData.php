<?php

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

final class ScheduleCalendarSettingsUpsertData extends Data
{
    /**
     * @param  array<int, int|string>  $reminderOffsets
     */
    public function __construct(
        #[MapInputName('include_school_calendar')]
        public bool $includeSchoolCalendar = true,
        #[MapInputName('include_exams')]
        public bool $includeExams = true,
        #[MapInputName('class_reminders_enabled')]
        public bool $classRemindersEnabled = false,
        #[MapInputName('reminder_offsets')]
        public array $reminderOffsets = [],
    ) {}

    public static function rules(): array
    {
        return [
            'include_school_calendar' => ['sometimes', 'boolean'],
            'include_exams' => ['sometimes', 'boolean'],
            'class_reminders_enabled' => ['sometimes', 'boolean'],
            'reminder_offsets' => ['nullable', 'array', 'max:2'],
            'reminder_offsets.*' => ['integer', 'in:5,10,15,30,60,120,180,1440'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'include_school_calendar' => __('包含學校行事曆'),
            'include_exams' => __('包含考試時段'),
            'class_reminders_enabled' => __('啟用面授提醒'),
            'reminder_offsets' => __('提醒時間'),
            'reminder_offsets.*' => __('提醒時間'),
        ];
    }
}
