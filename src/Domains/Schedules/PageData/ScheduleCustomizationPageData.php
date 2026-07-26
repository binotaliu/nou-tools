<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\PageData;

use App\Models\StudentSchedule;
use NouTools\Domains\Schedules\ViewModels\ScheduleCalendarSettingsViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleCustomLinkViewModel;
use NouTools\Domains\Schedules\ViewModels\ScheduleDisplayOptionsViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class ScheduleCustomizationPageData extends Resource
{
    /** @var array<int, int> */
    public const REMINDER_OFFSET_OPTIONS = [5, 10, 15, 30, 60, 120, 180, 1440];

    public function __construct(
        public StudentSchedule $schedule,
        public ScheduleDisplayOptionsViewModel $displayOptions,
        #[DataCollectionOf(ScheduleCustomLinkViewModel::class)]
        public DataCollection $customLinks,
    ) {}

    public static function defaultDisplayOptions(): ScheduleDisplayOptionsViewModel
    {
        return new ScheduleDisplayOptionsViewModel(
            showGreeting: true,
            showScheduleItems: true,
            showCommonLinks: true,
            showClassDates: true,
            showSchoolCalendar: true,
            showExamInfo: true,
            showAnnouncements: true,
            showShareSection: true,
            showPrintButton: true,
        );
    }

    /**
     * @param  array<string, bool|int|string|null>|null  $displayOptions
     */
    public static function normalizeDisplayOptions(?array $displayOptions): ScheduleDisplayOptionsViewModel
    {
        $defaults = self::defaultDisplayOptions();

        if (! is_array($displayOptions)) {
            return $defaults;
        }

        /** @var array<string, string> $propertyToKey */
        $propertyToKey = [
            'showGreeting' => 'show_greeting',
            'showScheduleItems' => 'show_schedule_items',
            'showCommonLinks' => 'show_common_links',
            'showClassDates' => 'show_class_dates',
            'showSchoolCalendar' => 'show_school_calendar',
            'showExamInfo' => 'show_exam_info',
            'showAnnouncements' => 'show_announcements',
            'showShareSection' => 'show_share_section',
            'showPrintButton' => 'show_print_button',
        ];

        $values = [];

        foreach ($propertyToKey as $property => $key) {
            $defaultValue = $defaults->{$property};
            $rawValue = $displayOptions[$key] ?? $defaultValue;
            $values[$property] = filter_var($rawValue, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaultValue;
        }

        return new ScheduleDisplayOptionsViewModel(...$values);
    }

    public static function defaultCalendarSettings(): ScheduleCalendarSettingsViewModel
    {
        return new ScheduleCalendarSettingsViewModel(
            includeSchoolCalendar: true,
            includeExams: true,
            classRemindersEnabled: false,
            reminderOffsets: [30],
        );
    }

    /**
     * @param  array<string, mixed>|null  $calendarSettings
     */
    public static function normalizeCalendarSettings(?array $calendarSettings): ScheduleCalendarSettingsViewModel
    {
        $defaults = self::defaultCalendarSettings();

        if (! is_array($calendarSettings)) {
            return $defaults;
        }

        $includeSchoolCalendar = filter_var($calendarSettings['include_school_calendar'] ?? $defaults->includeSchoolCalendar, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaults->includeSchoolCalendar;
        $includeExams = filter_var($calendarSettings['include_exams'] ?? $defaults->includeExams, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaults->includeExams;
        $classRemindersEnabled = filter_var($calendarSettings['class_reminders_enabled'] ?? $defaults->classRemindersEnabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $defaults->classRemindersEnabled;
        $reminderOffsets = self::normalizeReminderOffsets($calendarSettings['reminder_offsets'] ?? $defaults->reminderOffsets);

        if ($reminderOffsets === []) {
            $reminderOffsets = $defaults->reminderOffsets;
        }

        return new ScheduleCalendarSettingsViewModel(
            includeSchoolCalendar: $includeSchoolCalendar,
            includeExams: $includeExams,
            classRemindersEnabled: $classRemindersEnabled,
            reminderOffsets: $reminderOffsets,
        );
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
     */
    public static function normalizeCustomLinks(?array $customLinks): DataCollection
    {
        if (! is_array($customLinks)) {
            return ScheduleCustomLinkViewModel::collect([], DataCollection::class);
        }

        $links = collect($customLinks)
            ->filter(fn ($link) => is_array($link))
            ->map(function (array $link): ScheduleCustomLinkViewModel {
                return new ScheduleCustomLinkViewModel(
                    title: trim((string) ($link['title'] ?? '')),
                    url: trim((string) ($link['url'] ?? '')),
                );
            })
            ->filter(fn (ScheduleCustomLinkViewModel $link): bool => $link->title !== '' && $link->url !== '')
            ->take(20)
            ->values();

        return ScheduleCustomLinkViewModel::collect($links, DataCollection::class);
    }
}
