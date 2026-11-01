<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\Acorns\Actions\BackfillAcorns;

final class BackfillAcornsCommand extends Command
{
    protected $signature = 'acorns:backfill';

    protected $description = '依過去的自習室紀錄與學習進度補發橡實（可重複執行）';

    public function handle(BackfillAcorns $backfillAcorns): int
    {
        $result = $backfillAcorns();

        $this->info("已處理 {$result['schedules']} 份課表：自習室 {$result['study']} 顆、歡迎禮 {$result['welcome']} 顆橡實。");

        return self::SUCCESS;
    }
}
