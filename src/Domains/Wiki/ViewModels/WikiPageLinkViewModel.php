<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use App\Models\WikiPage;
use Spatie\LaravelData\Data;

/**
 * A page as a list entry or link target.
 */
final class WikiPageLinkViewModel extends Data
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $description,
        public string $url,
    ) {}

    public static function fromModel(WikiPage $page): self
    {
        return new self(
            slug: $page->slug,
            title: (string) $page->currentRevision?->title,
            description: $page->currentRevision?->description,
            url: route('wiki.show', $page->slug),
        );
    }
}
