<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Heading;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class HeadingAnchorRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        if (! $node instanceof HeadingAnchorNode) {
            throw new \InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        return new HtmlElement('a', [
            'class' => 'md-heading-anchor',
            'href' => '#'.$node->getSlug(),
            'aria-label' => '連結到此段落',
        ], '#');
    }
}
