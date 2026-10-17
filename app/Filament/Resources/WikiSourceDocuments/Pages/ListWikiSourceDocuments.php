<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiSourceDocuments\Pages;

use App\Filament\Resources\WikiSourceDocuments\WikiSourceDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWikiSourceDocuments extends ListRecords
{
    protected static string $resource = WikiSourceDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
