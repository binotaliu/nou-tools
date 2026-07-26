<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class DialogueSpeakerStartParser implements BlockStartParserInterface
{
    private const PATTERN = '/^\s*(?<speaker>[^：:（(]{1,20})(?:[（(](?<mood>[^）)]{1,10})[）)])?\s*[：:]\s*(?<text>.*)$/u';

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        if (! DialogueContext::isInsideDialogueContainer($parserState)) {
            return BlockStart::none();
        }

        $remainder = $cursor->getRemainder();

        if (! \preg_match(self::PATTERN, $remainder, $matches)) {
            return BlockStart::none();
        }

        $speaker = \trim($matches['speaker']);

        if ($speaker === '') {
            return BlockStart::none();
        }

        $mood = isset($matches['mood']) && $matches['mood'] !== '' ? \trim($matches['mood']) : null;
        $text = $matches['text'];

        $prefixLength = \mb_strlen($remainder, 'UTF-8') - \mb_strlen($text, 'UTF-8');
        $cursor->advanceBy($prefixLength);

        return BlockStart::of(new DialogueTurnParser($speaker, $mood))->at($cursor);
    }
}
