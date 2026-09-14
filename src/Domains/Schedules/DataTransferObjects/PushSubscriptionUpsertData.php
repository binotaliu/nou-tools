<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

final class PushSubscriptionUpsertData extends Data
{
    public function __construct(
        public string $endpoint,
        #[MapInputName('keys.p256dh')]
        public string $publicKey,
        #[MapInputName('keys.auth')]
        public string $authToken,
        #[MapInputName('content_encoding')]
        public ?string $contentEncoding = null,
    ) {}

    public static function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2048', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'content_encoding' => ['nullable', 'string'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'endpoint' => __('推播端點'),
            'keys.p256dh' => __('推播金鑰'),
            'keys.auth' => __('推播驗證碼'),
        ];
    }
}
