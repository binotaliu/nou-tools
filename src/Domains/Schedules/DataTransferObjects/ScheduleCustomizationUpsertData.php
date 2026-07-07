<?php

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

final class ScheduleCustomizationUpsertData extends Data
{
    /**
     * @param  array<string, bool|int|string|null>  $displayOptions
     * @param  array<int, array{title?: string|null, url?: string|null}>  $customLinks
     */
    public function __construct(
        #[MapInputName('display_options')]
        public array $displayOptions = [],
        #[MapInputName('custom_links')]
        public array $customLinks = [],
    ) {}

    public static function rules(): array
    {
        return [
            'display_options' => ['nullable', 'array'],
            'display_options.show_greeting' => ['sometimes', 'boolean'],
            'display_options.show_schedule_items' => ['sometimes', 'boolean'],
            'display_options.show_common_links' => ['sometimes', 'boolean'],
            'display_options.show_class_dates' => ['sometimes', 'boolean'],
            'display_options.show_school_calendar' => ['sometimes', 'boolean'],
            'display_options.show_exam_info' => ['sometimes', 'boolean'],
            'display_options.show_announcements' => ['sometimes', 'boolean'],
            'display_options.show_share_section' => ['sometimes', 'boolean'],
            'display_options.show_print_button' => ['sometimes', 'boolean'],
            'custom_links' => ['nullable', 'array', 'max:20'],
            'custom_links.*.title' => ['nullable', 'string', 'max:12', 'required_with:custom_links.*.url'],
            'custom_links.*.url' => [
                'nullable',
                'url:http,https',
                'max:2048',
                'required_with:custom_links.*.title',
                function (string $attribute, ?string $value, $fail) {
                    if ($value === null) {
                        return;
                    }

                    $host = parse_url($value, PHP_URL_HOST);

                    if (! is_string($host)) {
                        return $fail('自訂連結網址格式不正確。');
                    }

                    if ($host === 'nou.edu.tw' || str_ends_with($host, '.nou.edu.tw')) {
                        return;
                    }

                    if ($host === 'line.me' || $host === 'docs.google.com') {
                        return;
                    }

                    $fail('自訂連結僅限 *.nou.edu.tw、line.me、docs.google.com 網域。');
                },
            ],
        ];
    }

    public static function attributes(): array
    {
        return [
            'display_options' => __('顯示區塊設定'),
            'custom_links' => __('自訂連結'),
            'custom_links.*.title' => __('自訂連結標題'),
            'custom_links.*.url' => __('自訂連結網址'),
        ];
    }
}
