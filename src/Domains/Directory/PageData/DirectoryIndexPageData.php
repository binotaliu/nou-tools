<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\PageData;

use NouTools\Domains\Directory\ViewModels\CenterGroupViewModel;
use NouTools\Domains\Directory\ViewModels\LinkGroupViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class DirectoryIndexPageData extends Resource
{
    /**
     * @param  DataCollection<int, LinkGroupViewModel>  $linkGroups
     */
    public function __construct(
        #[DataCollectionOf(LinkGroupViewModel::class)]
        public DataCollection $linkGroups,
        public ?CenterGroupViewModel $centerGroup = null,
    ) {}
}
