<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Inline;

use League\CommonMark\Delimiter\DelimiterInterface;
use League\CommonMark\Delimiter\Processor\CacheableDelimiterProcessorInterface;
use League\CommonMark\Node\Inline\AbstractStringContainer;

final class MarkDelimiterProcessor implements CacheableDelimiterProcessorInterface
{
    public function getOpeningCharacter(): string
    {
        return '=';
    }

    public function getClosingCharacter(): string
    {
        return '=';
    }

    public function getMinLength(): int
    {
        return 2;
    }

    public function getDelimiterUse(DelimiterInterface $opener, DelimiterInterface $closer): int
    {
        if ($opener->getLength() < 2 || $closer->getLength() < 2) {
            return 0;
        }

        return 2;
    }

    public function process(AbstractStringContainer $opener, AbstractStringContainer $closer, int $delimiterUse): void
    {
        $mark = new Mark;

        $tmp = $opener->next();
        while ($tmp !== null && $tmp !== $closer) {
            $next = $tmp->next();
            $mark->appendChild($tmp);
            $tmp = $next;
        }

        $opener->insertAfter($mark);
    }

    public function getCacheKey(DelimiterInterface $closer): string
    {
        return '='.$closer->getLength();
    }
}
