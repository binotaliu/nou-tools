<?php

namespace NouTools\Domains\Articles\Actions;

use App\Enums\ArticleType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\MarkdownConverter;
use NouTools\Domains\Articles\ViewModels\ArticleIndexPageViewModel;

final class ShowArticleIndexPage
{
    public function __invoke(ArticleType $type): ?ArticleIndexPageViewModel
    {
        $path = resource_path("articles/{$type->directory()}/_index.md");

        if (! File::exists($path)) {
            return null;
        }

        $content = new HtmlString($this->buildConverter()->convert(File::get($path))->getContent());

        return new ArticleIndexPageViewModel(
            type: $type,
            indexContent: $content,
        );
    }

    private function buildConverter(): MarkdownConverter
    {
        $environment = new \League\CommonMark\Environment\Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new FrontMatterExtension);

        return new MarkdownConverter($environment);
    }
}
