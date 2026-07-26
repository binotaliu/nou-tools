<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Callout;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final readonly class CalloutContainerRenderer implements ContainerRendererInterface
{
    public function __construct(
        private string $type,
        private CalloutHtmlBuilder $htmlBuilder,
    ) {}

    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        return $this->htmlBuilder->build(
            $this->type,
            $node->getArgument(),
            $childRenderer->renderNodes($node->children()),
        );
    }
}
