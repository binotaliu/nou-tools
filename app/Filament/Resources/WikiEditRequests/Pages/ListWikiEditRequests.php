<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditRequests\Pages;

use App\Filament\Resources\WikiEditRequests\WikiEditRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListWikiEditRequests extends ListRecords
{
    protected static string $resource = WikiEditRequestResource::class;
}
