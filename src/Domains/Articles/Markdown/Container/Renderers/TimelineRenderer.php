<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;
use NouTools\Domains\Articles\Markdown\Support\InlineTextExtractor;

final class TimelineRenderer implements ContainerRendererInterface
{
    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $list = null;

        foreach ($node->children() as $child) {
            if ($child instanceof ListBlock) {
                $list = $child;

                break;
            }
        }

        $items = [];

        foreach ($list?->children() ?? [] as $listItem) {
            if (! $listItem instanceof ListItem) {
                continue;
            }

            $items[] = $this->buildItem($listItem, $childRenderer);
        }

        return new HtmlElement('ol', ['class' => 'md-timeline'], $items);
    }

    private function buildItem(ListItem $listItem, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $dot = new HtmlElement('span', ['class' => 'md-timeline-dot', 'aria-hidden' => 'true']);
        $body = $this->buildBody($listItem, $childRenderer);

        return new HtmlElement('li', ['class' => 'md-timeline-item'], [
            $dot,
            new HtmlElement('div', ['class' => 'md-timeline-body'], $body),
        ]);
    }

    private function buildBody(ListItem $listItem, ChildNodeRendererInterface $childRenderer): string
    {
        $paragraph = null;

        foreach ($listItem->children() as $child) {
            if ($child instanceof Paragraph) {
                $paragraph = $child;

                break;
            }
        }

        if ($paragraph === null) {
            return $childRenderer->renderNodes($listItem->children());
        }

        $firstInline = $paragraph->firstChild();
        $secondInline = $firstInline?->next();

        if ($firstInline instanceof Strong && $secondInline !== null) {
            $afterText = InlineTextExtractor::plainTextFrom($secondInline);

            if (\preg_match('/^\s*[：:]/u', $afterText)) {
                $label = new HtmlElement('span', ['class' => 'md-timeline-label'], $childRenderer->renderNodes([$firstInline]));
                $remainder = InlineTextExtractor::stripLeadingSeparator($afterText);

                return $label.Xml::escape($remainder);
            }
        }

        return $childRenderer->renderNodes($listItem->children());
    }
}
