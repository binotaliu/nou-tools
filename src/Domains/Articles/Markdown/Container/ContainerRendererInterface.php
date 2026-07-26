<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container;

use League\CommonMark\Renderer\ChildNodeRendererInterface;

interface ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable;
}
