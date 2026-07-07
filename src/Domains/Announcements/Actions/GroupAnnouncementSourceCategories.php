<?php

namespace NouTools\Domains\Announcements\Actions;

use App\Enums\AnnouncementSourceGroup;
use Illuminate\Support\Collection;

final readonly class GroupAnnouncementSourceCategories
{
    public function __construct(
        private ListAnnouncementSourceCategories $listAnnouncementSourceCategories,
    ) {}

    /**
     * @return Collection<string, Collection<string, Collection<int, string>>>
     */
    public function __invoke(): Collection
    {
        $sourceCategories = ($this->listAnnouncementSourceCategories)();
        $sourceGroups = config('announcements.source_groups', []);

        return collect(AnnouncementSourceGroup::cases())
            ->mapWithKeys(function (AnnouncementSourceGroup $group) use ($sourceCategories, $sourceGroups): array {
                $sourcesInGroup = $sourceCategories->filter(
                    fn (Collection $categories, string $source): bool => ($sourceGroups[$source] ?? AnnouncementSourceGroup::Administrative->value) === $group->value
                );

                return [$group->value => $sourcesInGroup];
            });
    }
}
