<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\Actions;

use App\Enums\CenterRegion;
use App\Enums\LinkGroup;
use Illuminate\Support\Collection;
use NouTools\Domains\Directory\PageData\DirectoryIndexPageData;
use NouTools\Domains\Directory\ViewModels\CenterGroupViewModel;
use NouTools\Domains\Directory\ViewModels\CenterItemViewModel;
use NouTools\Domains\Directory\ViewModels\LinkGroupViewModel;
use NouTools\Domains\Directory\ViewModels\LinkItemViewModel;
use NouTools\Domains\Directory\ViewModels\PhoneNumberViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class ShowDirectoryIndexPage
{
    public function __invoke(): DirectoryIndexPageData
    {
        $links = collect(config('directory.links', []));

        $linkGroups = collect(LinkGroup::cases())
            ->filter(fn (LinkGroup $group): bool => $group !== LinkGroup::Center)
            ->map(fn (LinkGroup $group): LinkGroupViewModel => new LinkGroupViewModel(
                group: $group->value,
                label: $group->label(),
                links: $this->linksForGroup($links, $group),
            ))
            ->filter(fn (LinkGroupViewModel $linkGroup): bool => $linkGroup->links->count() > 0)
            ->values();

        return new DirectoryIndexPageData(
            linkGroups: LinkGroupViewModel::collect($linkGroups->all(), DataCollection::class),
            centerGroup: $this->centerGroup(),
        );
    }

    /**
     * @param  Collection<string, array{name: string, url: string, group: string}>  $links
     * @return DataCollection<int, LinkItemViewModel>
     */
    private function linksForGroup(Collection $links, LinkGroup $group): DataCollection
    {
        $items = $links
            ->filter(fn (array $link): bool => $link['group'] === $group->value)
            ->map(fn (array $link): LinkItemViewModel => new LinkItemViewModel(
                name: $link['name'],
                url: $link['url'],
            ));

        return LinkItemViewModel::collect($items->values()->all(), DataCollection::class);
    }

    private function centerGroup(): ?CenterGroupViewModel
    {
        $regionOrder = collect(CenterRegion::cases())
            ->map(fn (CenterRegion $region): string => $region->value)
            ->flip();

        $items = collect(config('directory.centers', []))
            ->map(fn (array $center): CenterItemViewModel => new CenterItemViewModel(
                name: $center['name'],
                url: $center['url'],
                region: $center['region'],
                regionLabel: CenterRegion::from($center['region'])->label(),
                address: $center['address'],
                phone: PhoneNumberViewModel::collect($center['phone'], DataCollection::class),
                latitude: $center['latitude'],
                longitude: $center['longitude'],
                transportUrl: $center['transport_url'] ?? null,
                googleMapsUrl: $center['google_maps_url'] ?? null,
            ))
            ->sortBy(fn (CenterItemViewModel $item): int => $regionOrder[$item->region] ?? PHP_INT_MAX)
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return new CenterGroupViewModel(
            label: LinkGroup::Center->label(),
            centers: CenterItemViewModel::collect($items->all(), DataCollection::class),
        );
    }
}
