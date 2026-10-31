<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ShowStudyRoomProgressData extends Data
{
    public function __construct(
        public ?string $month = null,
        public ?string $week = null,
        public bool $includeInPerson = false,
    ) {}

    public static function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'includeInPerson' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Query strings are snake_case, matching the stats endpoint.
     *
     * @param  array<string, mixed>  $query
     */
    public static function parseQuery(array $query): self
    {
        return self::validateAndCreate([
            'month' => $query['month'] ?? null,
            'week' => $query['week'] ?? null,
            'includeInPerson' => filter_var($query['include_in_person'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
