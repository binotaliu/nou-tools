<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use App\Models\CafeMenuItem;
use Spatie\LaravelData\Data;

final class CafeMenuItemViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $emoji,
        public string $cupStyle,
        public ?string $description,
        public int $price,
    ) {}

    public static function fromModel(CafeMenuItem $item): self
    {
        return new self(
            id: $item->id,
            name: $item->name,
            emoji: $item->emoji,
            cupStyle: $item->cup_style,
            description: $item->description,
            price: $item->price,
        );
    }
}
