<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscountStoreCategories\Pages;

use App\Filament\Resources\DiscountStoreCategories\DiscountStoreCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscountStoreCategory extends CreateRecord
{
    protected static string $resource = DiscountStoreCategoryResource::class;
}
