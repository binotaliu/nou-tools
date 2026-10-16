<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use NouTools\Domains\Wiki\Support\WikiTextDiff;
use Spatie\LaravelData\Data;

/**
 * One line of a diff view: `equal`, `added`, `removed`, or `gap` (with the
 * number of hidden unchanged lines as its text).
 */
final class WikiDiffLineViewModel extends Data
{
    public function __construct(
        public string $type,
        public string $text,
    ) {}

    /**
     * @return list<self>
     */
    public static function between(string $base, string $new): array
    {
        return array_map(
            fn (array $line): self => new self($line['type'], $line['text']),
            WikiTextDiff::compare($base, $new, ignoreBlankLines: false)->hunks(),
        );
    }
}
