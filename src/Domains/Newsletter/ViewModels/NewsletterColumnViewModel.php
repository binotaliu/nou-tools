<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\ViewModels;

use App\Models\NewsletterColumn;
use NouTools\Domains\Newsletter\Actions\RenderNewsletterMarkdown;
use Spatie\LaravelData\Data;

final class NewsletterColumnViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $author,
        // Plain HTML string rendered from Markdown; see ArticleIndexPageData::$indexContent.
        public string $body,
    ) {}

    public static function fromModel(NewsletterColumn $column, RenderNewsletterMarkdown $renderMarkdown): self
    {
        return new self(
            id: $column->id,
            title: $column->title,
            author: $column->author,
            body: $renderMarkdown($column->body),
        );
    }
}
