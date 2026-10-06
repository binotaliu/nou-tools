<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\StudyRoom\Actions\SendStudyGoalReminders;

final class SendStudyGoalRemindersCommand extends Command
{
    protected $signature = 'study-room:send-goal-reminders';

    protected $description = '推播學習目標提醒給設定了提醒時間的自習室同學';

    public function handle(SendStudyGoalReminders $sendStudyGoalReminders): int
    {
        $sentCount = $sendStudyGoalReminders();

        $this->info($sentCount === 0 ? '沒有需要提醒的同學。' : "已提醒 {$sentCount} 位同學。");

        return self::SUCCESS;
    }
}
