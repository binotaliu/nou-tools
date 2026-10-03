<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ForgetScheduleDeviceData extends Data
{
    /**
     * @param  string|null  $pushEndpoint  This browser's push subscription, which only the browser knows.
     */
    public function __construct(
        public ?string $pushEndpoint = null,
    ) {}

    public static function rules(): array
    {
        return [
            'pushEndpoint' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
