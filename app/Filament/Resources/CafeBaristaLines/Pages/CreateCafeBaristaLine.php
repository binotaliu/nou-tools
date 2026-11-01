<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeBaristaLines\Pages;

use App\Filament\Resources\CafeBaristaLines\CafeBaristaLineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCafeBaristaLine extends CreateRecord
{
    protected static string $resource = CafeBaristaLineResource::class;
}
