<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class NewsletterReactionsViewModel extends Data
{
    /**
     * @param  DataCollection<int, NewsletterReactionOptionViewModel>  $options
     * @param  string|null  $mine  Key of the reaction this session chose, if any.
     */
    public function __construct(
        #[DataCollectionOf(NewsletterReactionOptionViewModel::class)]
        public DataCollection $options,
        public ?string $mine,
    ) {}
}
