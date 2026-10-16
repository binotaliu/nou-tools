<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

final readonly class WikiLinkTarget
{
    public function __construct(
        public string $slug,
        public string $title,
    ) {}
}
