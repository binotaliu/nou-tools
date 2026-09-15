<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\PageData;

use App\Enums\ArticleType;
use Spatie\LaravelData\Resource;

final class ArticleIndexPageData extends Resource
{
    public function __construct(
        public ArticleType $type,
        // Plain HTML string, not Illuminate\Support\HtmlString: spatie/laravel-data
        // has no transformer for HtmlString and silently serializes it to `{}` when
        // this ViewModel is passed to Inertia::render() as a prop. Vue renders it
        // with v-html.
        public string $indexContent,
    ) {}
}
