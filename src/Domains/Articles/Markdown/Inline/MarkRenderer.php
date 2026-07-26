<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Inline;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class MarkRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        if (! $node instanceof Mark) {
            throw new \InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        return new HtmlElement('mark', ['class' => 'md-mark'], $childRenderer->renderNodes($node->children()));
    }
}
