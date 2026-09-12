<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\StudyRoom\Actions\FillFloorWithTestStudents;
use NouTools\Domains\StudyRoom\Actions\ReleaseTestStudents;

/**
 * Local testing aid for the floor-opening rule: fill a floor with fake
 * students, watch the next floor open, then `--release` to clean up.
 */
final class FillStudyRoomFloorCommand extends Command
{
    protected $signature = 'study-room:fill-floor
        {floor=1 : 要塞滿的樓層}
        {--release : 改為釋放所有測試同學佔用的座位並刪除他們}';

    protected $description = '用測試同學塞滿自習室的某一層樓（測試下一層開放用）；加 --release 可全部清掉';

    public function handle(FillFloorWithTestStudents $fillFloorWithTestStudents, ReleaseTestStudents $releaseTestStudents): int
    {
        if (app()->isProduction()) {
            $this->error('這個指令只能在非正式環境使用。');

            return self::FAILURE;
        }

        if ($this->option('release')) {
            $releasedCount = $releaseTestStudents();
            $this->info("已釋放 {$releasedCount} 個測試同學的座位。");

            return self::SUCCESS;
        }

        $floor = (int) $this->argument('floor');

        if ($floor < 1 || $floor > (int) config('study-room.floors.max')) {
            $this->error('樓層超出範圍。');

            return self::FAILURE;
        }

        $filledCount = $fillFloorWithTestStudents($floor);
        $this->info("已在 {$floor} 樓塞入 {$filledCount} 位測試同學。用 --release 可全部清掉。");

        return self::SUCCESS;
    }
}
