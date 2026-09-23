<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangelogPosts\Pages;

use App\Filament\Resources\ChangelogPosts\ChangelogPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChangelogPosts extends ListRecords
{
    protected static string $resource = ChangelogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
