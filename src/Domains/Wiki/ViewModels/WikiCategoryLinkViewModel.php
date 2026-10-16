<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use Spatie\LaravelData\Data;

final class WikiCategoryLinkViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $url,
        public ?int $pageCount = null,
    ) {}

    public static function fromName(string $name, ?int $pageCount = null): self
    {
        return new self($name, route('wiki.category', ['category' => $name]), $pageCount);
    }
}
