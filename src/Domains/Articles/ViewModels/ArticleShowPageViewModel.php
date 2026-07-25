<?php

namespace NouTools\Domains\Articles\ViewModels;

use Illuminate\Support\HtmlString;
use Spatie\LaravelData\Data;

final class ArticleShowPageViewModel extends Data
{
    public function __construct(
        public ArticleViewModel $article,
        public ?HtmlString $sidebarContent,
    ) {}
}
