<?php

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Data;

final class StudentScheduleUpsertData extends Data
{
    public function __construct(
        public ?string $name,
        public array $items,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*' => ['required', 'exists:course_classes,id'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => __('課表名稱'),
            'items' => __('課程'),
        ];
    }
}
