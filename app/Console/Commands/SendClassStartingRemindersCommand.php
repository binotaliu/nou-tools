<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\Schedules\Actions\DispatchClassStartingReminders;

final class SendClassStartingRemindersCommand extends Command
{
    protected $signature = 'schedules:send-class-starting-reminders';

    protected $description = '對 10 分鐘後即將開始且有視訊連結的課程，發送 Web Push 提醒';

    public function handle(DispatchClassStartingReminders $dispatchClassStartingReminders): int
    {
        $sentCount = $dispatchClassStartingReminders();

        if ($sentCount === 0) {
            $this->info('沒有需要發送的提醒。');
        } else {
            $this->info("已發送 {$sentCount} 則提醒。");
        }

        return self::SUCCESS;
    }
}
