<?php

declare(strict_types=1);

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

        return collect(AnnouncementSourceGroup::cases())
            ->mapWithKeys(function (AnnouncementSourceGroup $group) use ($sourceCategories): array {
                $sourcesInGroup = $sourceCategories->filter(
                    fn (Collection $categories, string $source): bool => AnnouncementSourceGroup::forSource($source) === $group
                );

                return [$group->value => $sourcesInGroup];
            });
    }
}
