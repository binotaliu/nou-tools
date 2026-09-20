<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\ViewModels;

use Spatie\LaravelData\Data;

final class NewsletterReactionOptionViewModel extends Data
{
    public function __construct(
        public string $key,
        public string $emoji,
        public string $label,
        public int $count,
    ) {}
}
