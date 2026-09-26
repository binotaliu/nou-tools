<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;
use NouTools\Domains\Schedules\ViewModels\ScheduleBackupViewModel;

final readonly class ShowScheduleBackup
{
    public function __invoke(StudentSchedule $schedule): ScheduleBackupViewModel
    {
        $url = route('schedules.show', $schedule);

        return new ScheduleBackupViewModel(
            name: $schedule->name ?: '我的課表',
            url: $url,
            qrCodeSvg: $this->addSvgViewBox(DNS2D::getBarcodeSVG($url, 'QRCODE')),
        );
    }

    /**
     * milon/barcode's SVG has a fixed `width`/`height` but no `viewBox`, so
     * CSS cannot scale it; the backup card shows the code at screen size.
     */
    private function addSvgViewBox(string $svg): string
    {
        return preg_replace('/<svg width="(\d+)" height="(\d+)"/', '<svg viewBox="0 0 $1 $2" width="$1" height="$2"', $svg, 1) ?? $svg;
    }
}
