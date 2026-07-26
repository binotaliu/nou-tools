<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

use League\CommonMark\Parser\MarkdownParserStateInterface;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;

final class DialogueContext
{
    public static function isInsideDialogueContainer(MarkdownParserStateInterface $parserState): bool
    {
        for ($node = $parserState->getActiveBlockParser()->getBlock(); $node !== null; $node = $node->parent()) {
            if ($node instanceof ContainerNode && $node->getName() === 'dialogue') {
                return true;
            }
        }

        return false;
    }
}
