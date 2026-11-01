<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\DataTransferObjects;

use Spatie\LaravelData\Data;

final class OrderCoffeeData extends Data
{
    public function __construct(
        public int $menuItemId,
        public bool $toGo = false,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'menuItemId' => ['required', 'integer'],
            'toGo' => ['sometimes', 'boolean'],
        ];
    }
}
