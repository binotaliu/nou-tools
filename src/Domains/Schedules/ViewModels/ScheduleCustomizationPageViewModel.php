<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentSchedule;

final readonly class ScheduleCustomizationPageViewModel
{
    /** @var array<int, int> */
    public const REMINDER_OFFSET_OPTIONS = [5, 10, 15, 30, 60, 120, 180, 1440];

    /**
     * @param  array<string, bool>  $displayOptions
     * @param  array<int, array{title: string, url: string}>  $customLinks
     */
    public function __construct(
        public StudentSchedule $schedule,
        public array $displayOptions,
        public array $customLinks,
    ) {}

    /**
     * @return array<string, bool>
     */
    public static function defaultDisplayOptions(): array
    {
        return [
            'show_greeting' => true,
            'show_schedule_items' => true,
            'show_common_links' => true,
            'show_class_dates' => true,
            'show_school_calendar' => true,
            'show_exam_info' => true,
            'show_share_section' => true,
            'show_print_button' => true,
        ];
    }

    /**
     * @param  array<string, bool|int|string|null>|null  $displayOptions
     * @return array<string, bool>
     */
    public static function normalizeDisplayOptions(?array $displayOptions): array
    {
        $defaults = self::defaultDisplayOptions();

        if (! is_array($displayOptions)) {
            return $defaults;
        }

        foreach ($defaults as $key => $defaultValue) {
            $rawValue = $displayOptions[$key] ?? $defaultValue;
            $defaults[$key] = filter_var($rawValue, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaultValue;
        }

        return $defaults;
    }

    /**
     * @return array{include_school_calendar: bool, include_exams: bool, class_reminders_enabled: bool, reminder_offsets: array<int, int>}
     */
    public static function defaultCalendarSettings(): array
    {
        return [
            'include_school_calendar' => true,
            'include_exams' => true,
            'class_reminders_enabled' => false,
            'reminder_offsets' => [30],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $calendarSettings
     * @return array{include_school_calendar: bool, include_exams: bool, class_reminders_enabled: bool, reminder_offsets: array<int, int>}
     */
    public static function normalizeCalendarSettings(?array $calendarSettings): array
    {
        $defaults = self::defaultCalendarSettings();

        if (! is_array($calendarSettings)) {
            return $defaults;
        }

        $includeSchoolCalendar = filter_var($calendarSettings['include_school_calendar'] ?? $defaults['include_school_calendar'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaults['include_school_calendar'];
        $includeExams = filter_var($calendarSettings['include_exams'] ?? $defaults['include_exams'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaults['include_exams'];
        $classRemindersEnabled = filter_var($calendarSettings['class_reminders_enabled'] ?? $defaults['class_reminders_enabled'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaults['class_reminders_enabled'];
        $reminderOffsets = self::normalizeReminderOffsets($calendarSettings['reminder_offsets'] ?? $defaults['reminder_offsets']);

        if ($reminderOffsets === []) {
            $reminderOffsets = $defaults['reminder_offsets'];
        }

        return [
            'include_school_calendar' => $includeSchoolCalendar,
            'include_exams' => $includeExams,
            'class_reminders_enabled' => $classRemindersEnabled,
            'reminder_offsets' => $reminderOffsets,
        ];
    }

    /**
     * @param  array<int|string, int|string>|string|null  $reminderOffsets
     * @return array<int, int>
     */
    public static function normalizeReminderOffsets(array|string|null $reminderOffsets): array
    {
        if (is_string($reminderOffsets)) {
            $reminderOffsets = array_filter(explode(',', $reminderOffsets), fn (string $value): bool => trim($value) !== '');
        }

        if (! is_array($reminderOffsets)) {
            return [];
        }

        return collect($reminderOffsets)
            ->map(fn ($offset): int => (int) $offset)
            ->filter(fn (int $offset): bool => in_array($offset, self::REMINDER_OFFSET_OPTIONS, true))
            ->unique()
            ->take(2)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{title?: string|null, url?: string|null}>|null  $customLinks
     * @return array<int, array{title: string, url: string}>
     */
    public static function normalizeCustomLinks(?array $customLinks): array
    {
        if (! is_array($customLinks)) {
            return [];
        }

        return collect($customLinks)
            ->filter(fn ($link) => is_array($link))
            ->map(function (array $link): array {
                return [
                    'title' => trim((string) ($link['title'] ?? '')),
                    'url' => trim((string) ($link['url'] ?? '')),
                ];
            })
            ->filter(fn (array $link): bool => $link['title'] !== '' && $link['url'] !== '')
            ->take(20)
            ->values()
            ->all();
    }
}
