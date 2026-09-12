<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\StudyRoom\Actions\ReleaseIdleSeats;

final class ReleaseIdleStudyRoomSeatsCommand extends Command
{
    protected $signature = 'study-room:release-idle-seats';

    protected $description = '釋放閒置過久（超過 heartbeat 逾時時間）的自習室座位';

    public function handle(ReleaseIdleSeats $releaseIdleSeats): int
    {
        $releasedCount = $releaseIdleSeats();

        if ($releasedCount === 0) {
            $this->info('沒有需要釋放的閒置座位。');
        } else {
            $this->info("已釋放 {$releasedCount} 個閒置座位。");
        }

        return self::SUCCESS;
    }
}
