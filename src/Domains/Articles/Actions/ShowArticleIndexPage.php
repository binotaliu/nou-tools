<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Actions;

use App\Enums\ArticleType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;
use NouTools\Domains\Articles\PageData\ArticleIndexPageData;

final readonly class ShowArticleIndexPage
{
    public function __construct(private ArticleMarkdownConverterFactory $converterFactory) {}

    public function __invoke(ArticleType $type): ?ArticleIndexPageData
    {
        $path = resource_path("articles/{$type->directory()}/_index.md");

        if (! File::exists($path)) {
            return null;
        }

        $content = new HtmlString($this->converterFactory->make()->convert(File::get($path))->getContent());

        return new ArticleIndexPageData(
            type: $type,
            indexContent: $content,
        );
    }
}
