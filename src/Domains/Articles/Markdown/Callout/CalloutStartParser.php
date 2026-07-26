<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Callout;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final readonly class CalloutStartParser implements BlockStartParserInterface
{
    private const MARKER_PATTERN = '/^\[!(?<type>[A-Za-z]+)\][ \t]*(?<title>.*)$/u';

    public function __construct(private CalloutHtmlBuilder $htmlBuilder) {}

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '>') {
            return BlockStart::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();
        $cursor->advanceBy(1);
        $cursor->advanceBySpaceOrTab();

        if (! \preg_match(self::MARKER_PATTERN, $cursor->getRemainder(), $matches)) {
            return BlockStart::none();
        }

        $type = \mb_strtolower($matches['type']);

        if (! $this->htmlBuilder->isKnownType($type)) {
            return BlockStart::none();
        }

        $title = \trim($matches['title']) !== '' ? \trim($matches['title']) : null;

        $cursor->advanceToEnd();

        return BlockStart::of(new CalloutParser($type, $title))->at($cursor);
    }
}
