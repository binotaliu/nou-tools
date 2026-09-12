<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\StudyRoom\Actions\ReleaseSeatsWithoutTimer;

final class ReleaseStudyRoomSeatsWithoutTimerCommand extends Command
{
    protected $signature = 'study-room:release-seats-without-timer';

    protected $description = '釋放已入座但超過寬限時間仍未開始計時的自習室座位';

    public function handle(ReleaseSeatsWithoutTimer $releaseSeatsWithoutTimer): int
    {
        $releasedCount = $releaseSeatsWithoutTimer();

        if ($releasedCount === 0) {
            $this->info('沒有需要釋放的未計時座位。');
        } else {
            $this->info("已釋放 {$releasedCount} 個未計時座位。");
        }

        return self::SUCCESS;
    }
}
