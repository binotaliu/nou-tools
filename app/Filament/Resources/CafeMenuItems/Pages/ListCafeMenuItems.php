<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeMenuItems\Pages;

use App\Filament\Resources\CafeMenuItems\CafeMenuItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCafeMenuItems extends ListRecords
{
    protected static string $resource = CafeMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
