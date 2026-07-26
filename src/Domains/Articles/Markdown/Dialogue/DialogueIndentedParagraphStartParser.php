<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Block\ParagraphParser;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * A `:::dialogue` turn's body is prose, not code: a continuation paragraph
 * that happens to be indented 4+ spaces (e.g. for visual grouping in the
 * source) should stay a normal paragraph rather than becoming an indented
 * code block. This must run with a higher priority than CommonMark's
 * IndentedCodeStartParser (registered at -100) but otherwise defers to it
 * everywhere else, including fenced code blocks inside dialogue (which are
 * unaffected since they aren't "indented" in the CommonMark sense).
 */
final class DialogueIndentedParagraphStartParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if (! $cursor->isIndented() || $cursor->isBlank()) {
            return BlockStart::none();
        }

        if ($parserState->getActiveBlockParser()->getBlock() instanceof Paragraph) {
            return BlockStart::none();
        }

        if (! DialogueContext::isInsideDialogueContainer($parserState)) {
            return BlockStart::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();

        return BlockStart::of(new ParagraphParser)->at($cursor);
    }
}
