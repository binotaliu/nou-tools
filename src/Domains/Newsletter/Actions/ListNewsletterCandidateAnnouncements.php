<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Enums\AnnouncementSourceGroup;
use App\Enums\NewsletterSection;
use App\Models\Announcement;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final readonly class ListNewsletterCandidateAnnouncements
{
    /**
     * Announcements published (or, lacking a publish date, first fetched)
     * within the inclusive Taipei-date window, split into newsletter
     * sections by their source's group.
     *
     * @return Collection<string, EloquentCollection<int, Announcement>> keyed by NewsletterSection value
     */
    public function __invoke(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $announcements = $this->query($from, $to)->get();

        return collect(NewsletterSection::cases())
            ->mapWithKeys(fn (NewsletterSection $section): array => [
                $section->value => $announcements
                    ->filter(fn (Announcement $announcement): bool => NewsletterSection::forSourceGroup(
                        AnnouncementSourceGroup::forSource($announcement->source_name)
                    ) === $section)
                    ->values(),
            ]);
    }

    /**
     * @return Builder<Announcement>
     */
    public function query(CarbonInterface $from, CarbonInterface $to): Builder
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
