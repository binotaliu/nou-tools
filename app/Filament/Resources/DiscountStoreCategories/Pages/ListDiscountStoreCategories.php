<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStoreCategories\Pages;

use App\Filament\Resources\DiscountStoreCategories\DiscountStoreCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscountStoreCategories extends ListRecords
{
    protected static string $resource = DiscountStoreCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
