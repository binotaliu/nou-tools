<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class StudentScheduleUpsertData extends Data
{
    public function __construct(
        public ?string $name,
        public string $term,
        public array $items,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $term = $context->payload['term'] ?? null;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'term' => ['required', 'string', 'regex:/^\d{4}[ABC]$/'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*' => [
                'required',
                Rule::exists('course_classes', 'id')->where(
                    fn ($query) => $query->whereExists(
                        fn ($sub) => $sub->selectRaw('1')
                            ->from('courses')
                            ->whereColumn('courses.id', 'course_classes.course_id')
                            ->where('courses.term', $term)
                    )
                ),
            ],
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => __('課表名稱'),
            'term' => __('學期'),
            'items' => __('課程'),
        ];
    }
}
