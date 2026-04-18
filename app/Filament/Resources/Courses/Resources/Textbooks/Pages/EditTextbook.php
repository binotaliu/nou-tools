<?php

namespace App\Filament\Resources\Courses\Resources\Textbooks\Pages;

use App\Filament\Resources\Courses\Resources\Textbooks\TextbookResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTextbook extends EditRecord
{
    protected static string $resource = TextbookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
