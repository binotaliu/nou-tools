<?php

namespace NouTools\Domains\Articles\ViewModels;

use App\Enums\ArticleType;
use Illuminate\Support\HtmlString;

final readonly class ArticleIndexPageViewModel
{
    public function __construct(
        public ArticleType $type,
        public HtmlString $indexContent,
    ) {}
}
