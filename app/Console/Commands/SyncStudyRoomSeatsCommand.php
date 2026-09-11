<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\StudyRoom\Actions\SyncStudyRoomSeats;

final class SyncStudyRoomSeatsCommand extends Command
{
    protected $signature = 'study-room:sync-seats';

    protected $description = '將自習室座位與設定檔同步';

    public function handle(SyncStudyRoomSeats $syncStudyRoomSeats): int
    {
        $seatCount = $syncStudyRoomSeats();

        $this->info("自習室座位已同步，目前共 {$seatCount} 個座位。");

        return self::SUCCESS;
    }
}
