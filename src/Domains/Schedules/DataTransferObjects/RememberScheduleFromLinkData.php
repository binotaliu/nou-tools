<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Data;

final class RememberScheduleFromLinkData extends Data
{
    public function __construct(
        public string $url,
    ) {}

    public static function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'url' => __('課表連結'),
        ];
    }
}
