<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Toc;

use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class TocPlaceholderStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        if ($cursor->match('/^\[\[toc\]\][ \t]*$/i') === null) {
            return BlockStart::none();
        }

        return BlockStart::of(new class extends AbstractBlockContinueParser
        {
            private readonly TocPlaceholderNode $block;

            public function __construct()
            {
                $this->block = new TocPlaceholderNode;
            }

            public function getBlock(): TocPlaceholderNode
            {
                return $this->block;
            }

            public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
            {
                return BlockContinue::none();
            }
        })->at($cursor);
    }
}
