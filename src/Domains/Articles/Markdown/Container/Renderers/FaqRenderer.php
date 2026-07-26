<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Container\Renderers;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\StringContainerHelper;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererInterface;

final class FaqRenderer implements ContainerRendererInterface
{
    private const HEADING_LEVELS = [2, 3, 4];

    public function render(ContainerNode $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        $openFirst = \trim((string) $node->getArgument()) === 'open';

        $items = [];
        $question = null;
        /** @var Node[] $bodyNodes */
        $bodyNodes = [];

        $flush = function () use (&$items, &$question, &$bodyNodes, $childRenderer, $openFirst): void {
            if ($question === null) {
                return;
            }

            $summary = new HtmlElement('summary', ['class' => 'md-faq-question'], Xml::escape($question));
            $answer = new HtmlElement('div', ['class' => 'md-faq-answer'], $childRenderer->renderNodes($bodyNodes));

            $attributes = ['class' => 'md-faq-item'];
            if ($items === [] && $openFirst) {
                $attributes['open'] = true;
            }

            $items[] = new HtmlElement('details', $attributes, [$summary, $answer]);
        };

        foreach ($node->children() as $child) {
            if ($child instanceof Heading && \in_array($child->getLevel(), self::HEADING_LEVELS, true)) {
                $flush();
                $question = StringContainerHelper::getChildText($child);
                $bodyNodes = [];

                continue;
            }

            $bodyNodes[] = $child;
        }

        $flush();

        return new HtmlElement('div', ['class' => 'md-faq'], $items);
    }
}
