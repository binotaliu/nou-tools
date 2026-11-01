<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\Cafe\Actions\SyncCafeSeats;

final class SyncCafeSeatsCommand extends Command
{
    protected $signature = 'cafe:sync-seats';

    protected $description = '將咖啡廳座位與設定檔同步';

    public function handle(SyncCafeSeats $syncCafeSeats): int
    {
        $this->info("咖啡廳座位已同步，目前共 {$syncCafeSeats()} 個座位。");

        return self::SUCCESS;
    }
}
