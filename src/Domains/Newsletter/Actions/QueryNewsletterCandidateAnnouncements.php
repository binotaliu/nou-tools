<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Models\Announcement;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

final readonly class QueryNewsletterCandidateAnnouncements
{
    /**
     * Announcements published (or, lacking a publish date, first fetched)
     * within the inclusive Taipei-date window, newest first.
     *
     * @return Builder<Announcement>
     */
    public function __invoke(CarbonInterface $from, CarbonInterface $to): Builder
    {
        // Fetchers parse publish dates in Asia/Taipei and Eloquent stores them
        // without converting, so published_at holds Taipei wall-clock time;
        // fetched_at comes from the UTC app clock.
        $publishedStart = $from->toDateString().' 00:00:00';
        $publishedEnd = $to->toDateString().' 23:59:59';
        $fetchedStart = Date::parse($publishedStart, 'Asia/Taipei')->utc();
        $fetchedEnd = Date::parse($publishedEnd, 'Asia/Taipei')->utc();

        return Announcement::query()
            ->where(fn (Builder $query) => $query
                ->whereBetween('published_at', [$publishedStart, $publishedEnd])
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('published_at')
                    ->whereBetween('fetched_at', [$fetchedStart, $fetchedEnd])))
            ->orderByDesc('published_at')
            ->orderByDesc('fetched_at')
            ->orderByDesc('id');
    }
}
