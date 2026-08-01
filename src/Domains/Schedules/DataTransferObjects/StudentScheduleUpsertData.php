<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Illuminate\Support\Facades\DB;
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
            'items.*.course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where('term', $term),
            ],
            'items.*.class_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($context) {
                    if (! preg_match('/^items\.(\d+)\.class_id$/', $attribute, $matches)) {
                        return;
                    }

                    $courseId = $context->payload['items'][(int) $matches[1]]['course_id'] ?? null;

                    $belongsToCourse = DB::table('course_classes')
                        ->where('id', $value)
                        ->where('course_id', $courseId)
                        ->exists();

                    if (! $belongsToCourse) {
                        $fail(__('選擇的班級不屬於此課程。'));
                    }
                },
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
