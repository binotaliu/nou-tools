<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use App\Models\CafeOrder;
use Spatie\LaravelData\Data;

/**
 * The cup on someone's seat. Public: it carries no schedule identity. Nothing
 * shows until `readyAt` (the drink is still being made); `level` is how full
 * the cup is, 1 to 0 as it is sipped.
 */
final class CafeCoffeeViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $emoji,
        public string $cupStyle,
        public bool $toGo,
        public string $readyAt,
        public string $expiresAt,
        public ?int $menuItemId,
        public int $price,
        public int $sipsTaken,
        public int $sipsTotal,
        public float $level,
    ) {}

    public static function fromOrder(?CafeOrder $order): ?self
    {
        if ($order === null) {
            return null;
        }

        $sipsTotal = max(1, (int) config('cafe.coffee.sips'));
        $sipsTaken = min((int) $order->sips_taken, $sipsTotal);

        return new self(
            name: $order->name,
            emoji: $order->emoji,
            cupStyle: $order->cup_style,
            toGo: $order->to_go,
            readyAt: ($order->ready_at ?? $order->created_at)->toIso8601String(),
            expiresAt: $order->expires_at->toIso8601String(),
            menuItemId: $order->cafe_menu_item_id,
            price: $order->price_paid,
            sipsTaken: $sipsTaken,
            sipsTotal: $sipsTotal,
            level: round(1 - $sipsTaken / $sipsTotal, 2),
        );
    }
}
