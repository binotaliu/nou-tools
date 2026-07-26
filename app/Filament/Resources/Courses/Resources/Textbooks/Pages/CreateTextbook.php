<?php

declare(strict_types=1);

namespace App\Filament\Resources\Courses\Resources\Textbooks\Pages;

use App\Filament\Resources\Courses\Resources\Textbooks\TextbookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTextbook extends CreateRecord
{
    protected static string $resource = TextbookResource::class;
}
