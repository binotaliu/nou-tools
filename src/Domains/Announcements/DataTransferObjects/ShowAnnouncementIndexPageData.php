<?php

namespace NouTools\Domains\Announcements\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

final class ShowAnnouncementIndexPageData extends Data
{
    public function __construct(
        #[MapInputName('source_categories')]
        public array|string|null $sourceCategories = null,
        public string|array|null $source = null,
        public string|array|null $category = null,
    ) {}

    public static function rules(): array
    {
        return [
            'source_categories' => ['nullable', 'array'],
            'source_categories.*' => ['sometimes', 'array'],
            'source_categories.*.*' => ['sometimes', 'string'],
            'source' => ['nullable'],
            'source.*' => ['sometimes', 'string'],
            'category' => ['nullable'],
            'category.*' => ['sometimes', 'string'],
        ];
    }
}
