<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final class StepsRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $title = $node->getArgument();
        $list = null;

        foreach ($node->children() as $child) {
            if ($child instanceof ListBlock) {
                $list = $child;

                break;
            }
        }

        $items = [];
        $number = 1;

        foreach ($list?->children() ?? [] as $listItem) {
            if (! $listItem instanceof ListItem) {
                continue;
            }

            $marker = new HtmlElement('div', ['class' => 'md-step-marker', 'aria-hidden' => 'true'], (string) $number);
            $body = new HtmlElement('div', ['class' => 'md-step-body'], $childRenderer->renderNodes($listItem->children()));
            $items[] = new HtmlElement('li', ['class' => 'md-step'], [$marker, $body]);
            $number++;
        }

        $ol = new HtmlElement('ol', ['class' => 'md-steps', 'data-title' => $title ?? false], $items);

        if ($title === null) {
            return $ol;
        }

        $titleParagraph = new HtmlElement('p', ['class' => 'md-steps-title'], Xml::escape($title));

        return new HtmlElement('div', ['class' => 'md-steps-wrap'], [$titleParagraph, $ol]);
    }
}
