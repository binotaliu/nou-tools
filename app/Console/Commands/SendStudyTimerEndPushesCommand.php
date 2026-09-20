<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\StudyRoom\Actions\SendStudyTimerEndPushes;

/**
 * The schedule calls the action directly (see routes/console.php); this
 * wrapper exists so the sweep can be run by hand while debugging.
 */
final class SendStudyTimerEndPushesCommand extends Command
{
    protected $signature = 'study-room:send-timer-end-pushes';

    protected $description = '推播通知計時器剛結束的自習室同學';

    public function handle(SendStudyTimerEndPushes $sendStudyTimerEndPushes): int
    {
        $sentCount = $sendStudyTimerEndPushes();

        if ($sentCount === 0) {
            $this->info('沒有需要通知的計時器。');
        } else {
            $this->info("已通知 {$sentCount} 位同學。");
        }

        return self::SUCCESS;
    }
}
