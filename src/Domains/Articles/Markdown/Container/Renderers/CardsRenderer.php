<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;
use NouTools\Domains\Articles\Markdown\Support\InlineTextExtractor;

final class CardsRenderer implements ContainerRendererInterface
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

        $cards = [];

        foreach ($list?->children() ?? [] as $listItem) {
            if (! $listItem instanceof ListItem) {
                continue;
            }

            $card = $this->buildCard($listItem);

            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return new HtmlElement('div', ['class' => 'md-cards'], $cards);
    }

    private function buildCard(ListItem $listItem): ?HtmlElement
    {
        $paragraph = null;

        foreach ($listItem->children() as $child) {
            if ($child instanceof Paragraph) {
                $paragraph = $child;

                break;
            }
        }

        if ($paragraph === null) {
            return null;
        }

        $link = null;

        foreach ($paragraph->children() as $inline) {
            if ($inline instanceof Link) {
                $link = $inline;

                break;
            }
        }

        if ($link === null) {
            return null;
        }

        $title = InlineTextExtractor::plainText($link);
        $description = InlineTextExtractor::stripLeadingSeparator(InlineTextExtractor::plainTextFrom($link->next()));

        return new HtmlElement('a', ['class' => 'md-card', 'href' => $link->getUrl()], [
            new HtmlElement('span', ['class' => 'md-card-title'], Xml::escape($title)),
            new HtmlElement('span', ['class' => 'md-card-desc'], Xml::escape($description)),
        ]);
    }
}
