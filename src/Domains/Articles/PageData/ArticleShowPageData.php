<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\PageData;

use NouTools\Domains\Articles\ViewModels\ArticleViewModel;
use Spatie\LaravelData\Resource;

final class ArticleShowPageData extends Resource
{
    public function __construct(
        public ArticleViewModel $article,
        // Plain HTML string; see ArticleIndexPageData::$indexContent for why this
        // isn't Illuminate\Support\HtmlString.
        public ?string $sidebarContent,
    ) {}
}
