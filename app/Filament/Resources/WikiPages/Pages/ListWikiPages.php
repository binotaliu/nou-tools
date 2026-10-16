<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiPages\Pages;

use App\Filament\Resources\WikiPages\WikiPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWikiPages extends ListRecords
{
    protected static string $resource = WikiPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
