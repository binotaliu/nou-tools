<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeMenuItem;
use NouTools\Domains\Cafe\ViewModels\CafeMenuItemViewModel;

final readonly class ListCafeMenu
{
    /**
     * @return array<int, CafeMenuItemViewModel>
     */
    public function __invoke(): array
    {
        return CafeMenuItem::query()
            ->onMenu()
            ->get()
            ->map(fn (CafeMenuItem $item): CafeMenuItemViewModel => CafeMenuItemViewModel::fromModel($item))
            ->all();
    }
}
