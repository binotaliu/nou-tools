<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\DataTransferObjects;

use Spatie\LaravelData\Data;

final class SetTimerEndNotificationData extends Data
{
    public function __construct(
        public bool $enabled,
    ) {}

    public static function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'enabled' => '時間到時傳送通知',
        ];
    }
}
