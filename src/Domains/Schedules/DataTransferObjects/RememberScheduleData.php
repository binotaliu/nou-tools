<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Data;

final class RememberScheduleData extends Data
{
    /**
     * @param  bool  $remember  false signs in for this browser session only (a shared computer).
     */
    public function __construct(
        public bool $remember = true,
    ) {}

    public static function rules(): array
    {
        return [
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
