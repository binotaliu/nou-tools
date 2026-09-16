<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\ViewModels;

use App\Enums\ArticleType;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class ArticleViewModel extends Data
{
    public function __construct(
        public string $slug,
        public ArticleType $type,
        public string $title,
        public string $author,
        public CarbonInterface $publishedAt,
        public ?CarbonInterface $updatedAt,
        // Plain HTML string; see ArticleIndexPageData::$indexContent for why this
        // isn't Illuminate\Support\HtmlString.
        public string $content,
        public string $description,
    ) {}
}
