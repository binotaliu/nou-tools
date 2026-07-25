<?php

namespace NouTools\Domains\Articles\ViewModels;

use App\Enums\ArticleType;
use Illuminate\Support\HtmlString;
use Spatie\LaravelData\Data;

final class ArticleIndexPageViewModel extends Data
{
    public function __construct(
        public ArticleType $type,
        public HtmlString $indexContent,
    ) {}
}
