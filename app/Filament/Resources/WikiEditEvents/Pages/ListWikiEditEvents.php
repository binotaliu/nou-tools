<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditEvents\Pages;

use App\Filament\Resources\WikiEditEvents\WikiEditEventResource;
use Filament\Resources\Pages\ListRecords;

class ListWikiEditEvents extends ListRecords
{
    protected static string $resource = WikiEditEventResource::class;
}
