<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\Schedules\DataTransferObjects\RememberScheduleFromLinkData;

/**
 * Resolves the schedule a shared link (or a bare schedule id) points at, so a
 * viewer can save it to this browser. Accepts what the schedule page's share
 * URL and QR code carry: `https://…/schedules/{token}`, the legacy
 * `/schedule/{token}` form, or just the token itself.
 */
final class FindScheduleFromLink
{
    public function __invoke(RememberScheduleFromLinkData $input): StudentSchedule
    {
        $value = trim($input->url);

        $token = preg_match('#/schedules?/([A-Za-z0-9_-]+)#', $value, $matches) === 1
            ? $matches[1]
            : $value;

        $schedule = (new StudentSchedule)->resolveRouteBinding($token);

        if (! $schedule instanceof StudentSchedule) {
            throw ValidationException::withMessages([
                'url' => __('找不到這個課表，請確認連結是否完整正確。'),
            ]);
        }

        return $schedule;
    }
}
