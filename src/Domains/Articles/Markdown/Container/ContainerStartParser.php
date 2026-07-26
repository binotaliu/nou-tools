<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class ContainerStartParser implements BlockStartParserInterface
{
    private const PATTERN = '/^(:{3,})([a-z][a-z0-9-]*)(?:[ \t]+(.+?))?[ \t]*$/u';

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        if (! \preg_match(self::PATTERN, $cursor->getRemainder(), $matches)) {
            return BlockStart::none();
        }

        $fenceLength = \strlen($matches[1]);
        $name = $matches[2];
        $argument = isset($matches[3]) && $matches[3] !== '' ? $matches[3] : null;

        $cursor->advanceToEnd();

        return BlockStart::of(new ContainerParser($name, $argument, $fenceLength))->at($cursor);
    }
}
