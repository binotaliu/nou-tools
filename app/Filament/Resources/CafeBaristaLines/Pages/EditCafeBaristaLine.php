<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeBaristaLines\Pages;

use App\Filament\Resources\CafeBaristaLines\CafeBaristaLineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCafeBaristaLine extends EditRecord
{
    protected static string $resource = CafeBaristaLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
