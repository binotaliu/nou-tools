<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangelogPosts\Pages;

use App\Filament\Resources\ChangelogPosts\ChangelogPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChangelogPost extends CreateRecord
{
    protected static string $resource = ChangelogPostResource::class;
}
