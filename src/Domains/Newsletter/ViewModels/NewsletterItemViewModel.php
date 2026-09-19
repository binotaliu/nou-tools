<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\ViewModels;

use App\Models\NewsletterItem;
use NouTools\Domains\Newsletter\Actions\RenderNewsletterMarkdown;
use Spatie\LaravelData\Data;

final class NewsletterItemViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $sourceName,
        public string $headline,
        // Plain HTML string rendered from Markdown; see ArticleIndexPageData::$indexContent.
        public string $summary,
        public ?string $url,
    ) {}

    public static function fromModel(NewsletterItem $item, RenderNewsletterMarkdown $renderMarkdown): self
    {
        return new self(
            id: $item->id,
            sourceName: $item->source_name,
            headline: $item->headline,
            summary: $renderMarkdown($item->summary),
            url: $item->url,
        );
    }
}
