<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Toc;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerHelper;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

final class TocPlaceholderRenderer implements NodeRendererInterface
{
    private const LEVELS = [2, 3];

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        if (! $node instanceof TocPlaceholderNode) {
            throw new \InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        $root = $node;
        while ($root->parent() !== null) {
            $root = $root->parent();
        }

        $items = [];

        foreach ($root->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $candidate) {
            if (! $candidate instanceof Heading || ! \in_array($candidate->getLevel(), self::LEVELS, true)) {
                continue;
            }

            // Only top-level headings belong in the table of contents - headings
            // nested inside a container (e.g. `:::faq` questions), blockquote, or
            // list are excluded, though they keep their own anchor.
            if (! $candidate->parent() instanceof Document) {
                continue;
            }

            $slug = (string) $candidate->data->get('attributes/id');
            $text = StringContainerHelper::getChildText($candidate);

            $items[] = new HtmlElement('li', [], new HtmlElement('a', ['href' => '#'.$slug], Xml::escape($text)));
        }

        $title = new HtmlElement('p', ['class' => 'md-toc-title'], '目錄');

        if ($items === []) {
            return new HtmlElement('nav', ['class' => 'md-toc', 'aria-label' => '目錄'], [$title, new HtmlElement('ol')]);
        }

        return new HtmlElement('nav', ['class' => 'md-toc', 'aria-label' => '目錄'], [$title, new HtmlElement('ol', [], $items)]);
    }
}
