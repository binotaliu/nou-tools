<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\Cafe\Actions\ReleaseIdleCafeSeats;

final class ReleaseIdleCafeSeatsCommand extends Command
{
    protected $signature = 'cafe:release-idle-seats';

    protected $description = '釋放閒置過久的咖啡廳座位';

    public function handle(ReleaseIdleCafeSeats $releaseIdleCafeSeats): int
    {
        $this->info('已釋放 '.$releaseIdleCafeSeats().' 個閒置座位。');

        return self::SUCCESS;
    }
}
