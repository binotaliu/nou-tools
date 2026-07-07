<?php

namespace NouTools\Domains\Announcements\Actions;

use App\Models\Announcement;
use Illuminate\Support\Collection;

final readonly class ListLatestAnnouncementsForSourceCategories
{
    public function __construct(
        private FilterAnnouncementsBySourceCategories $filterAnnouncementsBySourceCategories,
    ) {}

    /**
     * @param  array<string, array<int, string>>  $selectedSourceCategories
     * @return Collection<int, Announcement>
     */
    public function __invoke(array $selectedSourceCategories, int $limit = 5): Collection
    {
        if ($selectedSourceCategories === []) {
            return collect();
        }

        return ($this->filterAnnouncementsBySourceCategories)(Announcement::query(), $selectedSourceCategories)
            ->orderByDesc('published_at')
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
