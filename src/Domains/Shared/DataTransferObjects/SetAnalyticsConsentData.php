<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\DataTransferObjects;

use Spatie\LaravelData\Data;

final class SetAnalyticsConsentData extends Data
{
    public function __construct(
        public bool $granted,
    ) {}

    public static function rules(): array
    {
        return [
            'granted' => ['required', 'boolean'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'granted' => '分析 Cookie 同意',
        ];
    }
}
