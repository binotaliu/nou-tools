<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Data;

final class PushUnsubscribeData extends Data
{
    public function __construct(
        public string $endpoint,
    ) {}

    public static function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2048'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'endpoint' => __('推播端點'),
        ];
    }
}
