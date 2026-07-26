<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final class SummaryRenderer implements ContainerRendererInterface
{
    private const DEFAULT_TITLE = '懶人包';

    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $title = $node->getArgument() ?? self::DEFAULT_TITLE;

        return new HtmlElement('aside', ['class' => 'md-summary'], [
            new HtmlElement('p', ['class' => 'md-summary-title'], Xml::escape($title)),
            new HtmlElement('div', ['class' => 'md-summary-body'], $childRenderer->renderNodes($node->children())),
        ]);
    }
}
