<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use App\Models\CafeOrder;
use Spatie\LaravelData\Data;

/**
 * The cup on someone's seat. Public: it carries no schedule identity.
 */
final class CafeCoffeeViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $emoji,
        public string $cupStyle,
        public bool $toGo,
        public string $expiresAt,
    ) {}

    public static function fromOrder(?CafeOrder $order): ?self
    {
        if ($order === null) {
            return null;
        }

        return new self(
            name: $order->name,
            emoji: $order->emoji,
            cupStyle: $order->cup_style,
            toGo: $order->to_go,
            expiresAt: $order->expires_at->toIso8601String(),
        );
    }
}
