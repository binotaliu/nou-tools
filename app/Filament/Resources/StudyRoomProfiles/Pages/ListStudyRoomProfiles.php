<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyRoomProfiles\Pages;

use App\Filament\Resources\StudyRoomProfiles\StudyRoomProfileResource;
use Filament\Resources\Pages\ListRecords;

final class ListStudyRoomProfiles extends ListRecords
{
    protected static string $resource = StudyRoomProfileResource::class;
}
