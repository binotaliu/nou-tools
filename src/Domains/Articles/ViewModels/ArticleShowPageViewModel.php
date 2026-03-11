<?php

namespace NouTools\Domains\Articles\ViewModels;

use Illuminate\Support\HtmlString;

final readonly class ArticleShowPageViewModel
{
    public function __construct(
        public ArticleViewModel $article,
        public ?HtmlString $sidebarContent,
    ) {}
}
