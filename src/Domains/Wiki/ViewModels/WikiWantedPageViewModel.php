<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use Spatie\LaravelData\Data;

/**
 * A page other pages link to but nobody has written yet (a red link).
 */
final class WikiWantedPageViewModel extends Data
{
    public function __construct(
        public string $slug,
        public int $linkCount,
        public string $createUrl,
    ) {}
}
