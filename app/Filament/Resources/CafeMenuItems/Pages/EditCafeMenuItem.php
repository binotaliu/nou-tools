<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeMenuItems\Pages;

use App\Filament\Resources\CafeMenuItems\CafeMenuItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCafeMenuItem extends EditRecord
{
    protected static string $resource = CafeMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
