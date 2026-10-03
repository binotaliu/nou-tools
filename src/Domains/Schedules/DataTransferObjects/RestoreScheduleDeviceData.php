<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Data;

final class RestoreScheduleDeviceData extends Data
{
    public function __construct(
        public string $token,
    ) {}

    public static function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:128'],
        ];
    }
}
