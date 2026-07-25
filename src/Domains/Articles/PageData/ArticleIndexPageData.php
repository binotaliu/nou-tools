<?php

namespace NouTools\Domains\Articles\PageData;

use App\Enums\ArticleType;
use Illuminate\Support\HtmlString;
use Spatie\LaravelData\Resource;

final class ArticleIndexPageData extends Resource
{
    public function __construct(
        public ArticleType $type,
        public HtmlString $indexContent,
    ) {}
}
