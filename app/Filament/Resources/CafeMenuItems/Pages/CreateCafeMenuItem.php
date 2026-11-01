<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeMenuItems\Pages;

use App\Filament\Resources\CafeMenuItems\CafeMenuItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCafeMenuItem extends CreateRecord
{
    protected static string $resource = CafeMenuItemResource::class;
}
