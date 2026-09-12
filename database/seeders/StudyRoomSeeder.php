<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use NouTools\Domains\StudyRoom\Actions\SyncStudyRoomSeats;

final class StudyRoomSeeder extends Seeder
{
    public function run(SyncStudyRoomSeats $syncStudyRoomSeats): void
    {
        $syncStudyRoomSeats();
    }
}
