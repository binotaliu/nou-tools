<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Extension\Footnote\Node\FootnoteContainer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * Footnotes are the wiki's citations, so they render as a 參考資料 section
 * instead of league/commonmark's bare `<div class="footnotes"><hr><ol>`.
 */
final class WikiReferencesRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        FootnoteContainer::assertInstanceOf($node);

        return new HtmlElement('section', [
            'class' => 'footnotes wiki-references',
            'role' => 'doc-endnotes',
            'aria-labelledby' => 'wiki-references',
        ], [
            new HtmlElement('h2', ['id' => 'wiki-references'], '參考資料'),
            new HtmlElement('ol', [], $childRenderer->renderNodes($node->children())),
        ]);
    }
}
