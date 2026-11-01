<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeBaristaLines\Pages;

use App\Filament\Resources\CafeBaristaLines\CafeBaristaLineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCafeBaristaLines extends ListRecords
{
    protected static string $resource = CafeBaristaLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
