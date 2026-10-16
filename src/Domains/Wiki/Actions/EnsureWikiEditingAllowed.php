<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\StudentSchedule;
use App\Models\WikiPage;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\Exceptions\WikiEditingNotAllowedException;

/**
 * Editing needs a browser signed in to a schedule (the closest thing to an
 * account), one an admin hasn't blocked, and a page that isn't protected.
 */
final readonly class EnsureWikiEditingAllowed
{
    /**
     * @throws WikiEditingNotAllowedException
     */
    public function __invoke(?StudentScheduleCookie $viewer, ?WikiPage $page): StudentSchedule
    {
        $schedule = $viewer === null ? null : StudentSchedule::query()->find($viewer->id);

        if ($schedule === null) {
            throw new WikiEditingNotAllowedException('請先在這台裝置登入你的課表，才能提出修改。');
        }

        if ($schedule->wiki_blocked_at !== null) {
            throw new WikiEditingNotAllowedException('這台裝置目前無法提出知識庫修改。');
        }

        if ($page?->is_protected) {
            throw new WikiEditingNotAllowedException('這個條目受到保護，目前只有管理員可以修改。');
        }

        return $schedule;
    }
}
