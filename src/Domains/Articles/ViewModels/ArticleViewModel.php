<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\ViewModels;

use App\Enums\ArticleType;
use Carbon\CarbonInterface;
use Illuminate\Support\HtmlString;
use Spatie\LaravelData\Data;

final class ArticleViewModel extends Data
{
    public HtmlString $content;

    public function __construct(
        public string $slug,
        public ArticleType $type,
        public string $title,
        public string $author,
        public CarbonInterface $publishedAt,
        public ?CarbonInterface $updatedAt,
        string $content,
        public string $description,
    ) {
        $this->content = new HtmlString($content);
    }
}
