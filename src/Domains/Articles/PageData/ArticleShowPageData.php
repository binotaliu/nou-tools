<?php

namespace NouTools\Domains\Articles\PageData;

use Illuminate\Support\HtmlString;
use NouTools\Domains\Articles\ViewModels\ArticleViewModel;
use Spatie\LaravelData\Resource;

final class ArticleShowPageData extends Resource
{
    public function __construct(
        public ArticleViewModel $article,
        public ?HtmlString $sidebarContent,
    ) {}
}
