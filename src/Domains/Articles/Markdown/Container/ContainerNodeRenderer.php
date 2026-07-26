<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final readonly class ContainerNodeRenderer implements NodeRendererInterface
{
    public function __construct(private ContainerRendererRegistry $registry) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        if (! $node instanceof ContainerNode) {
            throw new \InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        $renderer = $this->registry->get($node->getName());

        if ($renderer === null) {
            return new HtmlElement('div', ['class' => 'md-block'], $childRenderer->renderNodes($node->children()));
        }

        return $renderer->render($node, $childRenderer);
    }
}
