<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

final class DialogueTurnParser extends AbstractBlockContinueParser
{
    private readonly DialogueTurnNode $block;

    public function __construct(string $speaker, ?string $mood)
    {
        $this->block = new DialogueTurnNode($speaker, $mood);
    }

    public function getBlock(): DialogueTurnNode
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        // A new turn must never nest inside the previous one - rejecting it here
        // forces the engine to close this turn and re-attach the new turn as a
        // sibling under the enclosing `:::dialogue` container instead.
        return ! $childBlock instanceof DialogueTurnNode;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        return BlockContinue::at($cursor);
    }
}
