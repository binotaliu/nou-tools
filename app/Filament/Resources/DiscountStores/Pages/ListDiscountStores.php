<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStores\Pages;

use App\Filament\Resources\DiscountStores\DiscountStoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscountStores extends ListRecords
{
    protected static string $resource = DiscountStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
