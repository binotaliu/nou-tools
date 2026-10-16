<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\StudentSchedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use NouTools\Domains\Wiki\Exceptions\WikiDraftLimitReachedException;

/**
 * Every AI draft (the first one, each 再調整 and each redraft after a
 * conflict) costs one unit from the schedule's hourly and daily allowance,
 * the IP's hourly allowance and the site's daily cap. Nothing is consumed
 * unless all of them still have room.
 */
final readonly class ConsumeWikiDraftAllowance
{
    private const int HOUR = 3600;

    private const int DAY = 86400;

    /**
     * @throws WikiDraftLimitReachedException
     */
    public function __invoke(StudentSchedule $schedule, string $ipHash): void
    {
        $limits = [
            ["wiki-draft:schedule-hour:{$schedule->id}", (int) config('wiki.rate_limits.per_schedule_per_hour'), self::HOUR, '你這一小時內的修改次數已達上限，請稍後再試。'],
            ["wiki-draft:schedule-day:{$schedule->id}", (int) config('wiki.rate_limits.per_schedule_per_day'), self::DAY, '你今天的修改次數已達上限，請明天再試。'],
            ["wiki-draft:ip-hour:{$ipHash}", (int) config('wiki.rate_limits.per_ip_per_hour'), self::HOUR, '這個網路這一小時內的修改次數已達上限，請稍後再試。'],
        ];

        foreach ($limits as [$key, $maxAttempts, , $message]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new WikiDraftLimitReachedException($message);
            }
        }

        $siteKey = 'wiki-draft:site-day:'.Date::now('Asia/Taipei')->toDateString();

        if ((int) Cache::get($siteKey, 0) >= (int) config('wiki.ai.daily_cap')) {
            throw new WikiDraftLimitReachedException('知識庫今天的 AI 編輯額度已用完，請明天再試。');
        }

        foreach ($limits as [$key, , $decaySeconds]) {
            RateLimiter::hit($key, $decaySeconds);
        }

        Cache::add($siteKey, 0, self::DAY * 2);
        Cache::increment($siteKey);
    }
}
