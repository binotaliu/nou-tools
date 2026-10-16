<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Parses `[[slug]]`, `[[slug|顯示文字]]` and `[[slug#段落標題|顯示文字]]`.
 * Anything between the brackets that isn't a Latin slug (`[[空大]]`) is left
 * alone as plain text, and so is `[[toc]]`, which belongs to the table of
 * contents placeholder.
 */
final class WikiLinkParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\[\[([a-z0-9]+(?:-[a-z0-9]+)*)(?:#([^\[\]|\n]+))?(?:\|([^\[\]\n]+))?\]\]');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        [$slug, $anchor, $label] = $inlineContext->getSubMatches() + [null, null, null];

        if ($slug === null || $slug === 'toc') {
            return false;
        }

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new WikiLink(
            $slug,
            filled($anchor) ? trim($anchor) : null,
            filled($label) ? trim($label) : null,
        ));

        return true;
    }
}
