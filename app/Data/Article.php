<?php

namespace App\Data;

use App\Enums\ArticleType;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use Spatie\LaravelData\Data;

class Article extends Data
{
    public HtmlString $content;

    public function __construct(
        public string $slug,
        public ArticleType $type,
        public string $title,
        public string $author,
        public Carbon $publishedAt,
        string $content,
        public string $description,
    ) {
        $this->content = new HtmlString($content);
    }
}
