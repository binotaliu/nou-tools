<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Callout;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final readonly class CalloutNodeRenderer implements NodeRendererInterface
{
    public function __construct(private CalloutHtmlBuilder $htmlBuilder) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        if (! $node instanceof CalloutNode) {
            throw new \InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        return $this->htmlBuilder->build(
            $node->getType(),
            $node->getTitle(),
            $childRenderer->renderNodes($node->children()),
        );
    }
}
