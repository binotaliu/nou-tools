<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use App\Enums\AnnouncementSourceGroup;
use App\Enums\NewsletterSection;
use App\Models\Announcement;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class ListNewsletterCandidateAnnouncements
{
    public function __construct(
        private QueryNewsletterCandidateAnnouncements $queryNewsletterCandidateAnnouncements,
    ) {}

    /**
     * Announcements published (or, lacking a publish date, first fetched)
     * within the inclusive Taipei-date window, split into newsletter
     * sections by their source's group. The 藝文活動 section shares its
     * candidates with 空大新消息, so those announcements appear under both.
     *
     * @return Collection<string, EloquentCollection<int, Announcement>> keyed by NewsletterSection value
     */
    public function __invoke(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $announcements = ($this->queryNewsletterCandidateAnnouncements)($from, $to)->get();

        return collect(NewsletterSection::cases())
            ->mapWithKeys(fn (NewsletterSection $section): array => [
                $section->value => $announcements
                    ->filter(fn (Announcement $announcement): bool => NewsletterSection::forSourceGroup(
                        AnnouncementSourceGroup::forSource($announcement->source_name)
                    ) === $section->candidatePool())
                    ->values(),
            ]);
    }
}
