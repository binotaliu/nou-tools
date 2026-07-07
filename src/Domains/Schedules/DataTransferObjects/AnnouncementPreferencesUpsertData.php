<?php

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

final class AnnouncementPreferencesUpsertData extends Data
{
    /**
     * @param  array<string, array<int, string>>  $announcementCategories
     */
    public function __construct(
        #[MapInputName('announcement_categories')]
        public array $announcementCategories = [],
    ) {}

    public static function rules(): array
    {
        return [
            'announcement_categories' => ['nullable', 'array'],
            'announcement_categories.*' => ['array'],
            'announcement_categories.*.*' => ['string'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'announcement_categories' => __('公告分類設定'),
        ];
    }
}
