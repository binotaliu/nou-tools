<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeStickyNotes\Pages;

use App\Filament\Resources\CafeStickyNotes\CafeStickyNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListCafeStickyNotes extends ListRecords
{
    protected static string $resource = CafeStickyNoteResource::class;
}
