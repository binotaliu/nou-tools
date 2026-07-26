<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final class ChecklistRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        return new HtmlElement('div', ['class' => 'md-checklist'], $childRenderer->renderNodes($node->children()));
    }
}
