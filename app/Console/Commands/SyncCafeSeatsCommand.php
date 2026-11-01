<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\Cafe\Actions\SyncCafeSeats;

final class SyncCafeSeatsCommand extends Command
{
    protected $signature = 'cafe:sync-seats';

    protected $description = '依照組態設定同步咖啡廳座位';

    public function handle(SyncCafeSeats $syncCafeSeats): int
    {
        $this->info("已同步咖啡廳座位，共 {$syncCafeSeats()} 席。");

        return self::SUCCESS;
    }
}
